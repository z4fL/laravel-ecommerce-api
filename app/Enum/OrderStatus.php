<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING_PAYMENT = 'pending_payment'; // The order has been created, but payment is still required.

    case PAID = 'paid'; // Payment is confirmed and accepted.

    case PROCESSING = 'processing'; // The seller is preparing and packing your order.

    case SHIPPED = 'shipped'; // The item is moving through the carrier's network.

    case DELIVERED = 'delivered'; // The package has arrived at the customer address.

    case COMPLETED = 'completed'; // The full order lifecycle is finished and closed.

    case CANCELLED = 'cancelled'; // The order was canceled before completion.


    public function canTransitionTo(self $status): bool
    {
        $allowed = match ($this) {
            self::PENDING_PAYMENT => [
                self::PAID,
                self::CANCELLED,
            ],

            self::PAID => [
                self::PROCESSING,
                self::CANCELLED,
            ],

            self::PROCESSING => [
                self::SHIPPED,
            ],

            self::SHIPPED => [
                self::COMPLETED,
            ],

            self::COMPLETED,
            self::CANCELLED => [],
        };

        return in_array($status, $allowed, true);
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED,
            self::CANCELLED => true,

            default => false,
        };
    }
}
