<?php

namespace App\DataTransferObjects;

use App\Enum\PaymentOutcome;

class PaymentEventResult
{
    public function __construct(
        public int $paymentId,
        public PaymentOutcome $outcome,
    ) {}
}
