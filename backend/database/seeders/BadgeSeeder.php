<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['code' => 'first_steps', 'name' => 'First Steps', 'description' => 'Earn your first 10 points.', 'criteria_type' => 'points_threshold', 'criteria_value' => 10],
            ['code' => 'rising_star', 'name' => 'Rising Star', 'description' => 'Earn 100 points.', 'criteria_type' => 'points_threshold', 'criteria_value' => 100],
            ['code' => 'biology_enthusiast', 'name' => 'Biology Enthusiast', 'description' => 'Earn 500 points.', 'criteria_type' => 'points_threshold', 'criteria_value' => 500],
            ['code' => 'week_warrior', 'name' => 'Week Warrior', 'description' => 'Keep a 7-day study streak.', 'criteria_type' => 'streak_threshold', 'criteria_value' => 7],
            ['code' => 'consistency_champion', 'name' => 'Consistency Champion', 'description' => 'Keep a 30-day study streak.', 'criteria_type' => 'streak_threshold', 'criteria_value' => 30],
            ['code' => 'topic_master', 'name' => 'Topic Master', 'description' => 'Reach 80+ mastery in a topic.', 'criteria_type' => 'mastery_threshold', 'criteria_value' => 80],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
