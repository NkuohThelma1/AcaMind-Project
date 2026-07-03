<?php

namespace App\Services;

use App\Events\QuizAttemptCompleted;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuizEngineService
{
    private const TOPIC_PRACTICE_QUESTION_COUNT = 20;

    private const GCE_SIMULATION_QUESTION_COUNT = 50;

    private const GCE_SIMULATION_TIME_LIMIT_SECONDS = 90 * 60;

    private const MIN_DIFFICULTY = 1;

    private const MAX_DIFFICULTY = 5;

    private const START_DIFFICULTY = 3;

    /**
     * Start an adaptive topic-practice attempt and serve its first question.
     */
    public function startTopicPractice(User $user, Topic $topic): array
    {
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'type' => 'topic_practice',
            'topic_id' => $topic->id,
            'level_id' => $topic->level_id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_questions' => self::TOPIC_PRACTICE_QUESTION_COUNT,
        ]);

        $quizQuestion = $this->serveNextAdaptiveQuestion($attempt);

        return ['attempt' => $attempt, 'quiz_question' => $quizQuestion];
    }

    /**
     * Start a static, timed GCE simulation across all topics for a level.
     */
    public function startGceSimulation(User $user, Level $level): array
    {
        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'type' => 'gce_simulation',
            'topic_id' => null,
            'level_id' => $level->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_questions' => self::GCE_SIMULATION_QUESTION_COUNT,
            'time_limit_seconds' => self::GCE_SIMULATION_TIME_LIMIT_SECONDS,
        ]);

        $questions = $this->sampleGceQuestions($level);

        $quizQuestions = DB::transaction(function () use ($attempt, $questions) {
            return $questions->values()->map(function (Question $question, int $sequence) use ($attempt) {
                return QuizQuestion::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'sequence' => $sequence + 1,
                    'difficulty_at_time' => $question->difficulty,
                    'option_order' => $this->shuffledOptionOrder($question),
                ])->setRelation('question', $question);
            });
        });

        return ['attempt' => $attempt, 'quiz_questions' => $quizQuestions];
    }

    /**
     * Record an answer against a specific served question and either serve the
     * next adaptive question (topic practice) or complete the attempt once all
     * questions are answered.
     */
    public function submitAnswer(QuizAttempt $attempt, QuizQuestion $quizQuestion, ?string $selectedOption, int $timeTakenSeconds): array
    {
        $question = $quizQuestion->question;
        $isCorrect = $selectedOption !== null && $question->isCorrectOption($selectedOption);

        $attempt->answers()->create([
            'quiz_question_id' => $quizQuestion->id,
            'question_id' => $question->id,
            'selected_option' => $selectedOption,
            'is_correct' => $isCorrect,
            'time_taken_seconds' => $timeTakenSeconds,
            'answered_at' => now(),
        ]);

        $attempt->increment('correct_count', $isCorrect ? 1 : 0);
        $answeredCount = $attempt->answers()->count();

        if ($answeredCount >= $attempt->total_questions) {
            $attempt = $this->completeAttempt($attempt->fresh());

            return ['attempt' => $attempt, 'quiz_question' => null, 'completed' => true];
        }

        if ($attempt->type === 'topic_practice') {
            $nextQuizQuestion = $this->serveNextAdaptiveQuestion($attempt);

            return ['attempt' => $attempt->fresh(), 'quiz_question' => $nextQuizQuestion, 'completed' => false];
        }

        // GCE simulation: questions were pre-selected at start; return the next one in sequence.
        $nextQuizQuestion = $this->nextPreselectedQuestion($attempt);

        return ['attempt' => $attempt->fresh(), 'quiz_question' => $nextQuizQuestion, 'completed' => false];
    }

    private function completeAttempt(QuizAttempt $attempt): QuizAttempt
    {
        $scorePercent = round(($attempt->correct_count / max(1, $attempt->total_questions)) * 100, 2);

        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score_percent' => $scorePercent,
            'duration_seconds' => $attempt->started_at ? max(0, now()->getTimestamp() - $attempt->started_at->getTimestamp()) : null,
        ]);

        $attempt = $attempt->fresh();

        event(new QuizAttemptCompleted($attempt));

        return $attempt;
    }

    /**
     * Pick the next question at the target staircase difficulty and record a new
     * quiz_questions "serving" row for it (a question may be served more than once
     * per attempt if the topic's pool is small - each serving gets its own row so
     * quiz_answers, which is keyed to a specific serving, never collides).
     */
    private function serveNextAdaptiveQuestion(QuizAttempt $attempt): QuizQuestion
    {
        $targetDifficulty = $this->nextDifficulty($attempt);
        $servedQuestionIds = $attempt->quizQuestions()->pluck('question_id')->all();

        $question = Question::where('topic_id', $attempt->topic_id)
            ->where('is_active', true)
            ->whereNotIn('id', $servedQuestionIds)
            ->orderByRaw('ABS(CAST(difficulty AS SIGNED) - ?) ASC', [$targetDifficulty])
            ->inRandomOrder()
            ->first();

        // Pool exhausted (small seeded content set) - allow repeats, picking the
        // closest-difficulty question; a fresh quiz_questions "serving" row below
        // keeps this distinct from its earlier serving, so re-answering is safe.
        if (! $question) {
            $question = Question::where('topic_id', $attempt->topic_id)
                ->where('is_active', true)
                ->orderByRaw('ABS(CAST(difficulty AS SIGNED) - ?) ASC', [$targetDifficulty])
                ->inRandomOrder()
                ->first();
        }

        $quizQuestion = QuizQuestion::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'sequence' => $attempt->quizQuestions()->count() + 1,
            'difficulty_at_time' => $targetDifficulty,
            'option_order' => $this->shuffledOptionOrder($question),
        ]);

        return $quizQuestion->setRelation('question', $question);
    }

    /**
     * A fresh shuffle of the question's option keys for this serving, so the
     * correct answer's on-screen position varies each time and can't be crammed.
     */
    private function shuffledOptionOrder(Question $question): array
    {
        $keys = array_keys($question->options);
        shuffle($keys);

        return $keys;
    }

    private function nextDifficulty(QuizAttempt $attempt): int
    {
        $answers = $attempt->answers()->orderBy('id')->get();

        if ($answers->isEmpty()) {
            return self::START_DIFFICULTY;
        }

        $last = $answers->last();
        $lastDifficulty = $attempt->quizQuestions()->find($last->quiz_question_id)?->difficulty_at_time
            ?? self::START_DIFFICULTY;

        if (! $last->is_correct) {
            return max(self::MIN_DIFFICULTY, $lastDifficulty - 1);
        }

        $twoCorrectInARow = $answers->count() >= 2 && $answers->slice(-2)->every(fn ($a) => $a->is_correct);

        return $twoCorrectInARow
            ? min(self::MAX_DIFFICULTY, $lastDifficulty + 1)
            : $lastDifficulty;
    }

    private function nextPreselectedQuestion(QuizAttempt $attempt): ?QuizQuestion
    {
        $answeredQuizQuestionIds = $attempt->answers()->pluck('quiz_question_id')->all();

        return $attempt->quizQuestions()
            ->whereNotIn('id', $answeredQuizQuestionIds)
            ->with('question')
            ->orderBy('sequence')
            ->first();
    }

    private function sampleGceQuestions(Level $level): Collection
    {
        $topics = Topic::where('level_id', $level->id)->get();
        $totalWeight = max(1, $topics->sum('exam_weight'));

        $allocations = $topics->mapWithKeys(function (Topic $topic) use ($totalWeight) {
            $count = (int) round((self::GCE_SIMULATION_QUESTION_COUNT * $topic->exam_weight) / $totalWeight);

            return [$topic->id => $count];
        });

        $selected = collect();

        foreach ($topics as $topic) {
            $available = Question::where('topic_id', $topic->id)->where('is_active', true)->inRandomOrder()->get();
            $take = min($allocations[$topic->id], $available->count());
            $selected = $selected->merge($available->take($take));
        }

        // Top up shortfall (from rounding, or topics with too few questions) using any remaining pool.
        if ($selected->count() < self::GCE_SIMULATION_QUESTION_COUNT) {
            $usedIds = $selected->pluck('id')->all();
            $topUp = Question::where('level_id', $level->id)
                ->where('is_active', true)
                ->whereNotIn('id', $usedIds)
                ->inRandomOrder()
                ->take(self::GCE_SIMULATION_QUESTION_COUNT - $selected->count())
                ->get();
            $selected = $selected->merge($topUp);
        }

        return $selected->shuffle()->take(self::GCE_SIMULATION_QUESTION_COUNT);
    }
}
