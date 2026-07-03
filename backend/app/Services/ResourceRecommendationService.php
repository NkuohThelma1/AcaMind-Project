<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\StudyPlan;
use Illuminate\Support\Collection;

class ResourceRecommendationService
{
    private const MAX_RESOURCES_PER_TOPIC = 3;

    /**
     * Recommend approved learning resources for the topics in a student's active
     * study plan, ranked in the same weakest-topic-first order as the plan itself -
     * so the resource a student sees first is for the topic they most need it for.
     */
    public function recommendationsForPlan(StudyPlan $studyPlan): Collection
    {
        $topicsInPriorityOrder = $studyPlan->items()
            ->with('topic:id,name,code')
            ->orderBy('order')
            ->get()
            ->pluck('topic')
            ->unique('id');

        return $topicsInPriorityOrder
            ->map(function ($topic) {
                $resources = LearningResource::where('status', 'approved')
                    ->where('topic_id', $topic->id)
                    ->orderByDesc('created_at')
                    ->take(self::MAX_RESOURCES_PER_TOPIC)
                    ->get();

                return [
                    'topic_id' => $topic->id,
                    'topic_name' => $topic->name,
                    'resources' => $resources,
                ];
            })
            ->filter(fn (array $group) => $group['resources']->isNotEmpty())
            ->values();
    }
}
