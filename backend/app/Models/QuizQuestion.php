<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['quiz_attempt_id', 'question_id', 'sequence', 'difficulty_at_time', 'option_order'])]
class QuizQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'option_order' => 'array',
        ];
    }

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(QuizAnswer::class);
    }
}
