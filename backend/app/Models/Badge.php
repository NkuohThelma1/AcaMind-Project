<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'icon', 'criteria_type', 'criteria_value'])]
class Badge extends Model
{
    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }
}
