<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransGateway implements PaymentGatewayInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        Config::$serverKey = config('payment.midtrans.server_key');
        Config::$clientKey = config('payment.midtrans.client_key');
        Config::$isProduction = config('payment.midtrans.is_production');
        Config::$isSanitized = config('payment.midtrans.is_sanitized');
        Config::$is3ds = config('payment.midtrans.is_3ds');
    }

    public function createTransaction(array $payload)
    {
        $response = Snap::createTransaction($payload);
        return $response;
    }

    public function getTransaction(string $transactionId): array
    {
        return [];
    }

    public function cancelTransaction(string $transactionId): array
    {
        return [];
    }
}
