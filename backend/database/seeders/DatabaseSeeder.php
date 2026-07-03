<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LevelSeeder::class,
            SubjectSeeder::class,
            TopicSeeder::class,
            QuestionSeeder::class,
            GradeBandSeeder::class,
            SubscriptionPlanSeeder::class,
            BadgeSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@acamind.test'],
            [
                'name' => 'AcaMind Admin',
                'password' => 'password',
                'role' => 'admin',
            ]
        );
    }
}
