<?php

namespace App\Enum;

enum PaymentStatus: string
{
    case PENDING = 'pending'; // The order has been created, but payment is still required.

    case PAID = 'paid'; // Payment is confirmed and accepted.

    case FAILED = 'failed'; // Payment is failed.

    case EXPIRED = 'expired'; // Payment is expired.

    case CANCELLED = 'cancelled'; // payment is cancelled.

    case REFUNDED = 'refunded'; // Payment is refunded.

    const ALLOWED_TRANSITIONS = [
        self::PENDING->value => [
            self::PAID,
            self::FAILED,
            self::EXPIRED,
            self::CANCELLED,
        ],
        self::PAID->value => [
            self::REFUNDED,
            self::FAILED,
        ],
        self::FAILED->value => [],
        self::EXPIRED->value => [],
        self::CANCELLED->value => [],
        self::REFUNDED->value => [],
    ];

    public function canTransitionTo(self $status): bool
    {
        $allowedTransitions = self::ALLOWED_TRANSITIONS[$this->value] ?? [];

        return in_array($status, $allowedTransitions, true);
    }
}
