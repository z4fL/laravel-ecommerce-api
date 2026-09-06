<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\PaymentWebhookInterface;
use App\PaymentGateways\MidtransGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user()?->getAuthIdentifier() ?? $request->ip()
            );
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('login:'.$request->ip().'|'.$email),
            ];
        });

        RateLimiter::for('register', fn (Request $request) =>
            Limit::perMinute(5)->by('register:'.$request->ip())
        );

        RateLimiter::for('password-reset', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('password-reset:ip:'.$request->ip()),
                Limit::perMinute(3)->by('password-reset:email:'.$email),
            ];
        });

        RateLimiter::for('email-verification', fn (Request $request) =>
            Limit::perMinute(3)->by(
                'email-verification:'.($request->user()?->getAuthIdentifier() ?? $request->ip())
            )
        );
    }
}
