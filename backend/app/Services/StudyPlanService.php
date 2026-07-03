<?php

namespace App\Services;

use App\Models\StudyPlan;
use App\Models\Topic;
use App\Models\TopicMastery;
use App\Models\User;
use Illuminate\Support\Carbon;

class StudyPlanService
{
    private const MAX_ITEMS = 5;

    private const NEUTRAL_MASTERY = 50.0;

    private const DAYS_BETWEEN_ITEMS = 2;

    /**
     * Rank topics by weakness x exam weight and regenerate the student's active
     * study plan for this level - spacing items out rather than cramming, which
     * is what actually targets the study-habits factor.
     */
    public function regenerateForUser(User $user, int $levelId): StudyPlan
    {
        $user->studyPlans()->where('level_id', $levelId)->where('status', 'active')
            ->update(['status' => 'archived']);

        $topics = Topic::where('level_id', $levelId)->get();
        $masteryByTopic = TopicMastery::where('user_id', $user->id)
            ->where('level_id', $levelId)
            ->get()
            ->keyBy('topic_id');

        $ranked = $topics->map(function (Topic $topic) use ($masteryByTopic) {
            $masteryScore = (float) ($masteryByTopic->get($topic->id)?->mastery_score ?? self::NEUTRAL_MASTERY);

            return [
                'topic' => $topic,
                'mastery_score' => $masteryScore,
                'priority_score' => (100 - $masteryScore) * $topic->exam_weight,
            ];
        })->sortByDesc('priority_score')->take(self::MAX_ITEMS)->values();

        $plan = StudyPlan::create([
            'user_id' => $user->id,
            'level_id' => $levelId,
            'status' => 'active',
            'generated_at' => now(),
            'target_date' => now()->addDays(self::MAX_ITEMS * self::DAYS_BETWEEN_ITEMS)->toDateString(),
        ]);

        foreach ($ranked as $index => $entry) {
            $plan->items()->create([
                'topic_id' => $entry['topic']->id,
                'priority_score' => round($entry['priority_score'], 2),
                'recommended_action' => $this->recommendedActionFor($entry['mastery_score']),
                'target_quiz_count' => $entry['mastery_score'] < 30 ? 2 : 1,
                'due_at' => Carbon::today()->addDays(($index + 1) * self::DAYS_BETWEEN_ITEMS),
                'order' => $index,
            ]);
        }

        return $plan->load('items.topic');
    }

    private function recommendedActionFor(float $masteryScore): string
    {
        return match (true) {
            $masteryScore < 30 => 'retry_weak_questions',
            $masteryScore < 60 => 'practice_quiz',
            default => 'review_notes',
        };
    }
}
