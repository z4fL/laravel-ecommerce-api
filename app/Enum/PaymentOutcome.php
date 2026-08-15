<?php

namespace App\Enum;

enum PaymentOutcome: string
{
    case PENDING = 'pending'; // The order has been created, but payment is still required.

    case SUCCESS = 'success'; // Payment is confirmed and accepted.

    case FAILED = 'failed'; // Payment is failed.

    case EXPIRED = 'expired'; // Payment is expired.

    case CANCELLED = 'cancelled'; // payment is cancelled.
}
