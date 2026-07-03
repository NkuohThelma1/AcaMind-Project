<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'study_plan_id', 'topic_id', 'priority_score', 'recommended_action',
    'target_quiz_count', 'is_completed', 'completed_at', 'due_at', 'order',
])]
class StudyPlanItem extends Model
{
    protected function casts(): array
    {
        return [
            'priority_score' => 'decimal:2',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'due_at' => 'date',
        ];
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
