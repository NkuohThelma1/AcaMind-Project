<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'type', 'topic_id', 'level_id', 'status', 'started_at', 'completed_at',
    'total_questions', 'correct_count', 'score_percent', 'predicted_grade',
    'time_limit_seconds', 'duration_seconds',
])]
class QuizAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'score_percent' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    public function gradePrediction(): HasMany
    {
        return $this->hasMany(GradePrediction::class);
    }

    public function isGceSimulation(): bool
    {
        return $this->type === 'gce_simulation';
    }
}
