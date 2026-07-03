<?php

namespace App\Services;

use App\Models\GradeBand;
use App\Models\GradePrediction;
use App\Models\QuizAttempt;

class GradePredictionService
{
    /**
     * Map a completed GCE simulation's score onto the level's grade bands and
     * snapshot it, so the student can see their predicted grade trend over time -
     * this is what turns raw practice into reduced exam anxiety/motivation.
     */
    public function predictForAttempt(QuizAttempt $attempt): ?GradePrediction
    {
        if (! $attempt->isGceSimulation() || $attempt->score_percent === null) {
            return null;
        }

        $band = GradeBand::where('level_id', $attempt->level_id)
            ->where('min_percent', '<=', $attempt->score_percent)
            ->where('max_percent', '>=', $attempt->score_percent)
            ->first();

        $grade = $band->grade ?? 'U';

        $attempt->update(['predicted_grade' => $grade]);

        return GradePrediction::create([
            'user_id' => $attempt->user_id,
            'quiz_attempt_id' => $attempt->id,
            'level_id' => $attempt->level_id,
            'score_percent' => $attempt->score_percent,
            'predicted_grade' => $grade,
        ]);
    }
}
