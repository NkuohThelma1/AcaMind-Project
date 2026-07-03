<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\StartGceSimulationRequest;
use App\Http\Requests\Quiz\StartTopicPracticeRequest;
use App\Http\Requests\Quiz\SubmitAnswerRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Level;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Topic;
use App\Services\QuizEngineService;
use App\Services\SubscriptionGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizEngineService $quizEngine,
        private readonly SubscriptionGateService $subscriptionGate,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $attempts = $request->user()->quizAttempts()
            ->with(['topic', 'level'])
            ->latest()
            ->paginate(20);

        return response()->json($attempts);
    }

    public function startTopicPractice(StartTopicPracticeRequest $request): JsonResponse
    {
        abort_unless(
            $this->subscriptionGate->allows($request->user(), 'topic_quizzes_per_day'),
            403,
            "You've reached your plan's daily topic quiz limit. Please upgrade to continue."
        );

        $topic = Topic::findOrFail($request->integer('topic_id'));

        $result = $this->quizEngine->startTopicPractice($request->user(), $topic);
        $this->subscriptionGate->recordUsage($request->user(), 'topic_quizzes_per_day');

        return response()->json([
            'attempt' => $result['attempt'],
            'question' => $this->servedQuestion($result['quiz_question']),
        ], 201);
    }

    public function startGceSimulation(StartGceSimulationRequest $request): JsonResponse
    {
        abort_unless(
            $this->subscriptionGate->allows($request->user(), 'gce_simulations_per_month'),
            403,
            "You've reached your plan's monthly GCE simulation limit. Please upgrade to continue."
        );

        $level = Level::findOrFail($request->integer('level_id'));

        $result = $this->quizEngine->startGceSimulation($request->user(), $level);
        $this->subscriptionGate->recordUsage($request->user(), 'gce_simulations_per_month');

        return response()->json([
            'attempt' => $result['attempt'],
            'questions' => $result['quiz_questions']->map(fn (QuizQuestion $qq) => $this->servedQuestion($qq))->values(),
        ], 201);
    }

    public function show(Request $request, QuizAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        return response()->json(
            $attempt->load(['topic', 'level', 'answers.question'])
        );
    }

    public function abandon(Request $request, QuizAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);

        if ($attempt->status === 'in_progress') {
            $attempt->update(['status' => 'abandoned', 'completed_at' => now()]);
        }

        return response()->json($attempt->fresh());
    }

    public function answer(SubmitAnswerRequest $request, QuizAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403);
        abort_if($attempt->status !== 'in_progress', 422, 'This quiz attempt is no longer in progress.');

        $quizQuestion = QuizQuestion::with('question')
            ->where('quiz_attempt_id', $attempt->id)
            ->findOrFail($request->integer('quiz_question_id'));

        abort_if($quizQuestion->answer()->exists(), 422, 'This question has already been answered.');

        $selected = $request->input('selected_option');
        $question = $quizQuestion->question;

        $result = $this->quizEngine->submitAnswer(
            $attempt,
            $quizQuestion,
            $selected,
            $request->integer('time_taken_seconds')
        );

        return response()->json([
            'attempt' => $result['attempt'],
            'completed' => $result['completed'],
            'feedback' => [
                'quiz_question_id' => $quizQuestion->id,
                'question_id' => $question->id,
                'selected_option' => $selected,
                'correct_option' => $question->correct_option,
                'is_correct' => $selected !== null && $question->isCorrectOption($selected),
                'explanation' => $question->explanation,
            ],
            'next_question' => $result['quiz_question'] ? $this->servedQuestion($result['quiz_question']) : null,
        ]);
    }

    private function servedQuestion(QuizQuestion $quizQuestion): array
    {
        $question = $quizQuestion->question;
        $orderedKeys = $quizQuestion->option_order ?? array_keys($question->options);

        $orderedOptions = [];
        foreach ($orderedKeys as $key) {
            if (array_key_exists($key, $question->options)) {
                $orderedOptions[$key] = $question->options[$key];
            }
        }

        $questionPayload = (new QuestionResource($question))->toArray(request());
        $questionPayload['options'] = $orderedOptions;

        return [
            'quiz_question_id' => $quizQuestion->id,
            'question' => $questionPayload,
        ];
    }
}
