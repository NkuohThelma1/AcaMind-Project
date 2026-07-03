<?php

namespace App\Contracts;

use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * Swap point for MTN MoMo/Orange Money. The log driver (default) marks the
 * charge as successful immediately without contacting any real provider, so
 * subscription upgrade flows can be built and tested before a processor is
 * chosen.
 */
interface PaymentGatewayInterface
{
    public function charge(User $user, SubscriptionPlan $plan): PaymentResult;
}
