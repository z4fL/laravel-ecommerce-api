<?php

namespace App\Exceptions;

use RuntimeException;

class UnsupportedPaymentGatewayException extends RuntimeException
{
    public function __construct(string $gateway)
    {
        parent::__construct(
            "Unsupported payment gateway: {$gateway}."
        );
    }
}
