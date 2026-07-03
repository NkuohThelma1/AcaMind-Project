<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\TopicMastery;

class MasteryService
{
    /**
     * EMA smoothing factor - higher weights recent performance more heavily,
     * so mastery tracks a student's current standing rather than lifetime average.
     */
    private const ALPHA = 0.3;

    /**
     * Neutral prior for a topic never attempted before, so a single early
     * answer doesn't swing mastery to an extreme (0 or 100) immediately.
     */
    private const NEUTRAL_PRIOR = 50.0;

    public function updateForAttempt(QuizAttempt $attempt): void
    {
        $answers = $attempt->answers()->with('question')->orderBy('id')->get();

        foreach ($answers as $answer) {
            $this->applyObservation(
                $attempt->user_id,
                $answer->question->topic_id,
                $answer->question->level_id,
                $answer->is_correct
            );
        }
    }

    private function applyObservation(int $userId, int $topicId, int $levelId, bool $isCorrect): void
    {
        $mastery = TopicMastery::firstOrNew(['user_id' => $userId, 'topic_id' => $topicId]);
        $old = $mastery->exists ? (float) $mastery->mastery_score : self::NEUTRAL_PRIOR;

        $observation = $isCorrect ? 100 : 0;
        $new = round(self::ALPHA * $observation + (1 - self::ALPHA) * $old, 2);

        $mastery->level_id = $levelId;
        $mastery->mastery_score = $new;
        $mastery->attempts_count = ($mastery->attempts_count ?? 0) + 1;
        $mastery->correct_count = ($mastery->correct_count ?? 0) + ($isCorrect ? 1 : 0);
        $mastery->last_practiced_at = now();
        $mastery->trend = $new > $old ? 'improving' : ($new < $old ? 'declining' : 'stable');
        $mastery->save();
    }
}
