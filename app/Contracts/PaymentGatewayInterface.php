<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function createTransaction(array $payload);

    public function getTransaction(string $transactionId);

    public function cancelTransaction(string $transactionId);
}
