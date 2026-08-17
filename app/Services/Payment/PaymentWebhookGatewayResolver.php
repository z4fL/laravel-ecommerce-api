<?php

namespace App\Services\Payment;

use App\Contracts\PaymentWebhookInterface;
use App\Exceptions\UnsupportedPaymentGatewayException;
use App\PaymentGateways\MidtransGateway;
use InvalidArgumentException;

class PaymentWebhookGatewayResolver
{
    public function resolve(string $gateway): PaymentWebhookInterface
    {
        return match ($gateway) {
            'midtrans' => app(MidtransGateway::class),

            default => throw new UnsupportedPaymentGatewayException($gateway),
        };
    }
}
