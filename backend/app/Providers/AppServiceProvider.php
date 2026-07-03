<?php

namespace App\Providers;

use App\Contracts\AiServiceInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Services\Ai\GeminiDriver;
use App\Services\Ai\LogAiDriver;
use App\Services\Payments\LogPaymentDriver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, match (config('services.payments.driver', 'log')) {
            default => LogPaymentDriver::class,
        });

        $this->app->bind(AiServiceInterface::class, function () {
            $driver = config('services.ai.driver', 'gemini');
            $apiKey = config('services.gemini.api_key');

            if ($driver === 'gemini' && $apiKey) {
                return new GeminiDriver($apiKey, config('services.gemini.model'));
            }

            // No API key configured yet (or an unknown driver) - fall back to the
            // log driver so the chat feature stays usable without any provider.
            return new LogAiDriver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
