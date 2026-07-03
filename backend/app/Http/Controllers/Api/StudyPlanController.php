<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyPlanItem;
use App\Services\ResourceRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyPlanController extends Controller
{
    public function __construct(
        private readonly ResourceRecommendationService $resourceRecommendations,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $query = $request->user()->studyPlans()->where('status', 'active')->with('items.topic');

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        $plan = $query->latest('generated_at')->first();

        return response()->json($plan);
    }

    public function recommendedResources(Request $request): JsonResponse
    {
        $query = $request->user()->studyPlans()->where('status', 'active');

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        $plan = $query->latest('generated_at')->first();

        if (! $plan) {
            return response()->json([]);
        }

        return response()->json($this->resourceRecommendations->recommendationsForPlan($plan));
    }

    public function completeItem(Request $request, StudyPlanItem $item): JsonResponse
    {
        abort_unless($item->studyPlan->user_id === $request->user()->id, 403);

        $item->update(['is_completed' => true, 'completed_at' => now()]);

        return response()->json($item);
    }
}
