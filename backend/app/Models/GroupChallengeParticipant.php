<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['group_challenge_id', 'user_id', 'quiz_attempt_id', 'score_percent', 'completed_at'])]
class GroupChallengeParticipant extends Model
{
    protected function casts(): array
    {
        return [
            'score_percent' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function groupChallenge(): BelongsTo
    {
        return $this->belongsTo(GroupChallenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }
}
