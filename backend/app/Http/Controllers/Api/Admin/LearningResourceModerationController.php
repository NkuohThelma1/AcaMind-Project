<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LearningResource\RejectLearningResourceRequest;
use App\Models\LearningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningResourceModerationController extends Controller
{
    public function index(): JsonResponse
    {
        $resources = LearningResource::where('status', 'pending')
            ->with(['topic:id,name,code', 'creator:id,name,email'])
            ->orderBy('created_at')
            ->get();

        return response()->json($resources);
    }

    public function approve(Request $request, LearningResource $learningResource): JsonResponse
    {
        abort_unless($learningResource->status === 'pending', 422, 'Resource is not pending review.');

        $learningResource->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return response()->json($learningResource->fresh());
    }

    public function reject(RejectLearningResourceRequest $request, LearningResource $learningResource): JsonResponse
    {
        abort_unless($learningResource->status === 'pending', 422, 'Resource is not pending review.');

        $learningResource->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return response()->json($learningResource->fresh());
    }
}
