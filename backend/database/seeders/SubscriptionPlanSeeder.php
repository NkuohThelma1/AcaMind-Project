<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionPlan::updateOrCreate(
            ['code' => 'free'],
            [
                'name' => 'Free',
                'price_xaf' => 0,
                'billing_interval' => 'monthly',
                'features' => [
                    'topic_quizzes_per_day' => 3,
                    'gce_simulations_per_month' => 2,
                    'study_plan' => true,
                    'parent_email' => false,
                    'peer_groups' => true,
                    'teacher_analytics' => false,
                    'paper_marking' => false,
                    'ai_coach_messages_per_day' => 10,
                ],
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['code' => 'premium'],
            [
                'name' => 'Premium',
                'price_xaf' => 2000,
                'billing_interval' => 'monthly',
                'features' => [
                    'topic_quizzes_per_day' => null,
                    'gce_simulations_per_month' => null,
                    'study_plan' => true,
                    'parent_email' => true,
                    'peer_groups' => true,
                    'teacher_analytics' => true,
                    'paper_marking' => false,
                    'ai_coach_messages_per_day' => null,
                ],
            ]
        );

        SubscriptionPlan::updateOrCreate(
            ['code' => 'premium_plus'],
            [
                'name' => 'Premium+',
                'price_xaf' => 3500,
                'billing_interval' => 'monthly',
                'features' => [
                    'topic_quizzes_per_day' => null,
                    'gce_simulations_per_month' => null,
                    'study_plan' => true,
                    'parent_email' => true,
                    'peer_groups' => true,
                    'teacher_analytics' => true,
                    'paper_marking' => true,
                    'ai_coach_messages_per_day' => null,
                ],
            ]
        );
    }
}
