<?php

namespace App\DataTransferObjects;

use App\Enum\PaymentOutcome;

class PaymentEventResult
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public int $paymentId,
        public PaymentOutcome $outcome,
    ) {}
}
