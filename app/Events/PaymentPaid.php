<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaymentPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $paymentId,
    ) {}
}
