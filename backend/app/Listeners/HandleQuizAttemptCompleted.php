<?php

namespace App\Listeners;

use App\Events\QuizAttemptCompleted;
use App\Services\GamificationService;
use App\Services\GradePredictionService;
use App\Services\MasteryService;
use App\Services\StudyPlanService;

/**
 * Orchestrates post-completion side effects in a fixed, dependency-safe order:
 * mastery must be updated before the study plan is regenerated from it. A
 * single listener (rather than several independently-ordered ones) guarantees
 * that sequencing regardless of how Laravel's event discovery orders classes.
 */
class HandleQuizAttemptCompleted
{
    public function __construct(
        private readonly MasteryService $masteryService,
        private readonly StudyPlanService $studyPlanService,
        private readonly GradePredictionService $gradePredictionService,
        private readonly GamificationService $gamificationService,
    ) {
    }

    public function handle(QuizAttemptCompleted $event): void
    {
        $attempt = $event->attempt;

        $this->masteryService->updateForAttempt($attempt);
        $this->studyPlanService->regenerateForUser($attempt->user, $attempt->level_id);
        $this->gradePredictionService->predictForAttempt($attempt);
        $this->gamificationService->awardForQuizAttempt($attempt);
    }
}
