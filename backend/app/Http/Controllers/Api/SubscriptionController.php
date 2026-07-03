<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans(): JsonResponse
    {
        return response()->json(SubscriptionPlan::all());
    }

    public function current(Request $request): JsonResponse
    {
        return response()->json($request->user()->activeSubscription()?->load('plan'));
    }

    public function subscribe(Request $request, PaymentGatewayInterface $paymentGateway): JsonResponse
    {
        $request->validate(['plan_code' => ['required', 'string', 'exists:subscription_plans,code']]);

        $plan = SubscriptionPlan::where('code', $request->string('plan_code'))->firstOrFail();
        $user = $request->user();

        $result = $paymentGateway->charge($user, $plan);

        abort_unless($result->success, 402, $result->errorMessage ?? 'Payment failed.');

        $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
            'payment_reference' => $result->reference,
        ]);

        return response()->json($subscription->load('plan'), 201);
    }
}
