<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentResult;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogPaymentDriver implements PaymentGatewayInterface
{
    public function charge(User $user, SubscriptionPlan $plan): PaymentResult
    {
        $reference = 'log_' . Str::uuid();

        Log::channel('single')->info('Payment (log driver - not actually charged)', [
            'user_id' => $user->id,
            'plan' => $plan->code,
            'price_xaf' => $plan->price_xaf,
            'reference' => $reference,
        ]);

        return new PaymentResult(success: true, reference: $reference);
    }
}
