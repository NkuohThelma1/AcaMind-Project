<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'feature_key', 'period', 'used_count'])]
class FeatureUsage extends Model
{
    protected $table = 'feature_usage';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
