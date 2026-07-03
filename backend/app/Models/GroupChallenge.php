<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['peer_group_id', 'topic_id', 'starts_at', 'ends_at', 'question_count', 'status'])]
class GroupChallenge extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function peerGroup(): BelongsTo
    {
        return $this->belongsTo(PeerGroup::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupChallengeParticipant::class);
    }
}
