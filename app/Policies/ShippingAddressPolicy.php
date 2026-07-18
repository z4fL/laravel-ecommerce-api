<?php

namespace App\Policies;

use App\Enum\UserRole;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ShippingAddressPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === UserRole::ADMIN
            ? true
            : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ShippingAddress $shippingAddress): Response
    {
        return $user->is($shippingAddress->user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->isCustomer() || $user->role->isSeller();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ShippingAddress $shippingAddress): Response
    {
        return $user->is($shippingAddress->user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ShippingAddress $shippingAddress): Response
    {
        return $this->update($user, $shippingAddress);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ShippingAddress $shippingAddress): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ShippingAddress $shippingAddress): bool
    {
        return false;
    }
}
