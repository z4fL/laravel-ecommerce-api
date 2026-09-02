<?php

namespace App\Policies;

use App\Enum\UserRole;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
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
            'view',
            'viewAny',
            'update',
            'delete',
            'restore',
            'forceDelete' => true,

            'create' => null,

            default => null,
        };
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
    public function view(User $user, Product $product): Response
    {
        return $user->id === $product->store->user_id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return ($user->role === UserRole::SELLER
            && $user->store()->exists())
            ? Response::allow()
            : Response::deny('Only sellers can create products.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): Response
    {
        return $user->is($product->store->user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): Response
    {
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): Response
    {
        return $this->update($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }

    /**
     * Determine whether the user can add model to cart.
     */
    public function addToCart(User $user, Product $product): Response
    {
        return $user->id !== $product->store->user_id
            ? Response::allow()
            : Response::deny('You cannot add your own product to the cart.');
    }
}
