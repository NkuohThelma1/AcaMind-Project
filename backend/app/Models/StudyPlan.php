<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'level_id', 'status', 'generated_at', 'target_date'])]
class StudyPlan extends Model
{
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'target_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudyPlanItem::class)->orderBy('order');
    }
}
