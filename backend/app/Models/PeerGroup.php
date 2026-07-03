<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['name', 'level_id', 'join_code', 'created_by'])]
class PeerGroup extends Model
{
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PeerGroupMember::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(GroupChallenge::class);
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(GroupMessage::class, 'messageable')->orderBy('created_at');
    }
}
