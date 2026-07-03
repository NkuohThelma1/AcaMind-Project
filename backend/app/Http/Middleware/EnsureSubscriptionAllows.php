<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAllows
{
    public function __construct(private readonly SubscriptionGateService $gate)
    {
    }

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user || ! $this->gate->allows($user, $featureKey)) {
            abort(403, 'Your current plan does not allow this action. Please upgrade to continue.');
        }

        return $next($request);
    }
}
