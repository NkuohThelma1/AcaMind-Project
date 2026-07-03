<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasteryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->topicMastery()->with('topic');

        if ($request->boolean('weak_only')) {
            $query->where('mastery_score', '<', 50);
        }

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        $mastery = $query->orderBy('mastery_score')->get()->map(function ($row) {
            return [
                'topic_id' => $row->topic_id,
                'topic_name' => $row->topic->name,
                'level_id' => $row->level_id,
                'mastery_score' => (float) $row->mastery_score,
                'is_weak' => $row->isWeak(),
                'trend' => $row->trend,
                'attempts_count' => $row->attempts_count,
                'correct_count' => $row->correct_count,
                'last_practiced_at' => $row->last_practiced_at,
            ];
        });

        return response()->json($mastery);
    }
}
