<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'level_id', 'parent_topic_id', 'code', 'name', 'exam_weight', 'order'])]
class Topic extends Model
{
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function parentTopic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'parent_topic_id');
    }

    public function subTopics(): HasMany
    {
        return $this->hasMany(Topic::class, 'parent_topic_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function topicMastery(): HasMany
    {
        return $this->hasMany(TopicMastery::class);
    }
}
