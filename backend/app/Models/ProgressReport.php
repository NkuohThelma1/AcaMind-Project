<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'parent_id', 'period_start', 'period_end', 'content', 'status', 'sent_at', 'provider_message_id'])]
class ProgressReport extends Model
{
    protected $table = 'progress_reports';

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(ParentContact::class, 'parent_id');
    }
}
