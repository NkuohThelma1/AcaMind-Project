<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id', 'topic_id', 'question_reference', 'submitted_text', 'file_path',
    'status', 'graded_by', 'ai_feedback', 'score', 'feedback_text', 'graded_at',
])]
class PaperSubmission extends Model
{
    protected function casts(): array
    {
        return [
            'ai_feedback' => 'array',
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
