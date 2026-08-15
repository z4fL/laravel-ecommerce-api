<?php

namespace App\Services\Payment;

use App\Contracts\PaymentWebhookInterface;
use App\PaymentGateways\MidtransGateway;
use InvalidArgumentException;

class PaymentWebhookGatewayResolver
{
    public function resolve(string $gateway): PaymentWebhookInterface
    {
        return match ($gateway) {
            'midtrans' => app(MidtransGateway::class),

            default => throw new InvalidArgumentException(
                'Unsupported webhook gateway.'
            ),
        };
    }
}
