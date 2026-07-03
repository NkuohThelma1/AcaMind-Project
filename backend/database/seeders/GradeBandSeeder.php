<?php

namespace Database\Seeders;

use App\Models\GradeBand;
use App\Models\Level;
use Illuminate\Database\Seeder;

class GradeBandSeeder extends Seeder
{
    /**
     * Approximation of GCE grading (A/B/C/D/E/U), tunable later without code changes.
     */
    public function run(): void
    {
        $bands = [
            ['grade' => 'A', 'min_percent' => 80, 'max_percent' => 100],
            ['grade' => 'B', 'min_percent' => 70, 'max_percent' => 79.99],
            ['grade' => 'C', 'min_percent' => 60, 'max_percent' => 69.99],
            ['grade' => 'D', 'min_percent' => 50, 'max_percent' => 59.99],
            ['grade' => 'E', 'min_percent' => 40, 'max_percent' => 49.99],
            ['grade' => 'U', 'min_percent' => 0, 'max_percent' => 39.99],
        ];

        foreach (Level::all() as $level) {
            foreach ($bands as $band) {
                GradeBand::updateOrCreate(
                    ['level_id' => $level->id, 'grade' => $band['grade']],
                    ['min_percent' => $band['min_percent'], 'max_percent' => $band['max_percent']]
                );
            }
        }
    }
}
