<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'topic_id', 'level_id', 'mastery_score', 'attempts_count', 'correct_count', 'last_practiced_at', 'trend'])]
class TopicMastery extends Model
{
    protected $table = 'topic_mastery';

    protected function casts(): array
    {
        return [
            'mastery_score' => 'decimal:2',
            'last_practiced_at' => 'datetime',
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

    public function isWeak(): bool
    {
        return (float) $this->mastery_score < 50;
    }
}
