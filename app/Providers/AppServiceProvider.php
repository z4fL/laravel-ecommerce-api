<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookInterface;
use App\PaymentGateways\MidtransGateway;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGatewayInterface::class,
            function (Application $app) {
                return match (config('payment.default')) {
                    'midtrans' => $app->make(MidtransGateway::class),

                    default => throw new InvalidArgumentException(
                        'Unsupported payment driver.'
                    ),
                };
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
