<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\PointsLedger;
use App\Models\QuizAttempt;
use App\Models\TopicMastery;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Carbon;

class GamificationService
{
    private const POINTS_PER_CORRECT_ANSWER = 5;

    private const COMPLETION_BONUS = 20;

    private const GCE_SIMULATION_BONUS = 50;

    public function awardForQuizAttempt(QuizAttempt $attempt): void
    {
        $user = $attempt->user;

        $points = $attempt->correct_count * self::POINTS_PER_CORRECT_ANSWER + self::COMPLETION_BONUS;
        if ($attempt->isGceSimulation()) {
            $points += self::GCE_SIMULATION_BONUS;
        }

        $this->recordPoints($user, $points, 'quiz_completed', $attempt);
        $this->updateStreak($user);
        $this->checkBadges($user->fresh());
    }

    private function recordPoints(User $user, int $points, string $reason, ?QuizAttempt $reference = null): void
    {
        PointsLedger::create([
            'user_id' => $user->id,
            'points' => $points,
            'reason' => $reason,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
        ]);

        $user->increment('total_points', $points);
    }

    private function updateStreak(User $user): void
    {
        $today = Carbon::today();
        $lastActivity = $user->last_activity_date ? Carbon::parse($user->last_activity_date) : null;

        if ($lastActivity !== null && $lastActivity->isSameDay($today)) {
            return; // already active today, streak unchanged
        }

        $user->current_streak_days = ($lastActivity !== null && $lastActivity->isSameDay($today->copy()->subDay()))
            ? $user->current_streak_days + 1
            : 1;

        $user->longest_streak_days = max($user->longest_streak_days, $user->current_streak_days);
        $user->last_activity_date = $today;
        $user->save();
    }

    private function checkBadges(User $user): void
    {
        foreach (Badge::all() as $badge) {
            $earned = match ($badge->criteria_type) {
                'points_threshold' => $user->total_points >= $badge->criteria_value,
                'streak_threshold' => $user->current_streak_days >= $badge->criteria_value,
                'mastery_threshold' => TopicMastery::where('user_id', $user->id)
                    ->where('mastery_score', '>=', $badge->criteria_value)
                    ->exists(),
                default => false,
            };

            if ($earned) {
                UserBadge::firstOrCreate(
                    ['user_id' => $user->id, 'badge_id' => $badge->id],
                    ['awarded_at' => now()]
                );
            }
        }
    }
}
