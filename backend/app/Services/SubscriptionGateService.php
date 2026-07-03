<?php

namespace App\Services;

use App\Models\FeatureUsage;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionGateService
{
    /**
     * Whether the user's active plan permits this feature right now. Boolean
     * feature flags (e.g. "paper_marking") are checked directly; numeric
     * limits (e.g. "topic_quizzes_per_day") are checked against a usage
     * counter for the current period - null means unlimited.
     */
    public function allows(User $user, string $featureKey): bool
    {
        $plan = $user->activeSubscription()?->plan;
        $value = $plan?->feature($featureKey, false);

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return true; // unlimited
        }

        $used = FeatureUsage::where('user_id', $user->id)
            ->where('feature_key', $featureKey)
            ->where('period', $this->periodFor($featureKey))
            ->value('used_count') ?? 0;

        return $used < $value;
    }

    public function recordUsage(User $user, string $featureKey): void
    {
        $usage = FeatureUsage::firstOrCreate(
            ['user_id' => $user->id, 'feature_key' => $featureKey, 'period' => $this->periodFor($featureKey)],
            ['used_count' => 0]
        );

        $usage->increment('used_count');
    }

    private function periodFor(string $featureKey): string
    {
        return str_ends_with($featureKey, '_per_day')
            ? Carbon::today()->toDateString()
            : Carbon::today()->format('Y-m');
    }
}
