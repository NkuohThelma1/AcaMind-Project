<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'topic_id', 'level_id', 'created_by', 'title', 'description', 'type',
    'url', 'file_path', 'status', 'reviewed_by', 'reviewed_at', 'rejection_reason',
])]
class LearningResource extends Model
{
    protected $appends = ['resource_url'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    protected function resourceUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->url ?? ($this->file_path ? Storage::disk('public')->url($this->file_path) : null),
        );
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
