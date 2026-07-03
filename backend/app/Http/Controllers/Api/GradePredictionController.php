<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradePredictionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->gradePredictions()->orderBy('created_at');

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        return response()->json($query->get());
    }
}
