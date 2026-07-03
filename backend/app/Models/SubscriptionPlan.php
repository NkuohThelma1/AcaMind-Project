<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'price_xaf', 'billing_interval', 'features'])]
class SubscriptionPlan extends Model
{
    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        return data_get($this->features, $key, $default);
    }
}
