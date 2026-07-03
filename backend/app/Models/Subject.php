<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Subject extends Model
{
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }
}
