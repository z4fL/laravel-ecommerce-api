<?php

namespace App\Policies;

use App\Enum\UserRole;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartItemPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->role !== UserRole::ADMIN) {
            return null;
        }

        return match ($ability) {
            'update', 'delete' => true,
            default => null,
        };
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CartItem $cartItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CartItem $item): Response
    {
        return $user->id === $item->cart->user_id
            ? Response::allow()
            : Response::deny('You do not own this cart item.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CartItem $item): Response
    {
         return $this->update($user, $item);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CartItem $item): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CartItem $item): bool
    {
        return false;
    }
}
