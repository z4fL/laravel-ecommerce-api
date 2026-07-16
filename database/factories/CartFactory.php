<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }

    public function withItems(int $count = 1): static
    {
        return $this->has(
            CartItem::factory()->count($count),
            'cartItems'
        );
    }

    public function forSeller(): static
    {
        return $this->for(
            User::factory()->seller()
        );
    }

    public function forCustomer(): static
    {
        return $this->for(
            User::factory()->customer()
        );
    }
}
