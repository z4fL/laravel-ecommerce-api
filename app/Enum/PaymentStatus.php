<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending'; // The order has been created, but payment is still required.

    case PAID = 'paid'; // Payment is confirmed and accepted.

    case FAILED = 'failed'; // Payment is failed.

    case EXPIRED = 'expired'; // Payment is expired.

    case CANCELLED = 'cancelled'; // payment is cancelled.

    case REFUNDED = 'refunded'; // Payment is refunded..
}
