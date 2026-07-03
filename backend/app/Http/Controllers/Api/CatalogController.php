<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function levels(): JsonResponse
    {
        return response()->json(Level::all());
    }

    public function topics(Request $request): JsonResponse
    {
        $query = Topic::query()->orderBy('order');

        if ($levelId = $request->integer('level_id')) {
            $query->where('level_id', $levelId);
        }

        return response()->json($query->get());
    }
}
