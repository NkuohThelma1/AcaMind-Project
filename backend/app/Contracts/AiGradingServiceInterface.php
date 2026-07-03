<?php

namespace App\Contracts;

use App\Models\PaperSubmission;

/**
 * Swap point for a real AI-assisted marking backend (Premium+ "paper marking").
 * No implementation is bound yet - submissions stay in pending_manual_review
 * status, reviewed by a teacher_verified user, until this is wired up.
 */
interface AiGradingServiceInterface
{
    public function grade(PaperSubmission $submission): void;
}
