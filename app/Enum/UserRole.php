<?php

namespace App\Enum;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SELLER = 'seller';
    case CUSTOMER = 'customer';

    /**
     * Determine whether the current role can access the required role.
     */
    public function canAccess(self $requiredRole): bool
    {
        return match ($this) {
            self::ADMIN => true,
            self::SELLER => in_array(
                $requiredRole,
                [self::SELLER, self::CUSTOMER],
                true
            ),
            self::CUSTOMER => $requiredRole === self::CUSTOMER,
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isSeller(): bool
    {
        return $this === self::SELLER;
    }

    public function isCustomer(): bool
    {
        return $this === self::CUSTOMER;
    }
}
