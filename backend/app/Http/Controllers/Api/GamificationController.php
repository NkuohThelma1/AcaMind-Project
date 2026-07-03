<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'total_points' => $user->total_points,
            'current_streak_days' => $user->current_streak_days,
            'longest_streak_days' => $user->longest_streak_days,
            'badges' => $user->badges()->with('badge')->orderByDesc('awarded_at')->get()->map(fn ($ub) => [
                'code' => $ub->badge->code,
                'name' => $ub->badge->name,
                'description' => $ub->badge->description,
                'icon' => $ub->badge->icon,
                'awarded_at' => $ub->awarded_at,
            ]),
            'recent_points' => $user->pointsLedger()->latest()->take(10)->get(['points', 'reason', 'created_at']),
        ]);
    }
}
