<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'name', 'phone_number', 'email', 'relationship', 'locale', 'is_active'])]
class ParentContact extends Model
{
    protected $table = 'parents';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function progressReports(): HasMany
    {
        return $this->hasMany(ProgressReport::class, 'parent_id');
    }
}
