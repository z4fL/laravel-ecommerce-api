<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    public function createTransaction(Payment $payment): array;

    public function getTransaction(string $transactionId): array;

    public function cancelTransaction(string $transactionId): array;
}
