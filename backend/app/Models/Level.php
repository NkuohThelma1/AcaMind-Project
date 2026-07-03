<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name'])]
class Level extends Model
{
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function gradeBands(): HasMany
    {
        return $this->hasMany(GradeBand::class);
    }
}
