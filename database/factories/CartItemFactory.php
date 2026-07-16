<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'price_snapshot' => 0
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (CartItem $item) {
            if ($item->relationLoaded('product')) {
                $item->price_snapshot = $item->product->price;
            }
        })->afterCreating(function (CartItem $item) {
            $item->updateQuietly([
                'price_snapshot' => $item->product->price
            ]);
        });
    }

    public function quantity(int $quantity): static
    {
        return $this->state([
            'quantity' => $quantity,
        ]);
    }

    public function snapshot(int $price): static
    {
        return $this->state([
            'price_snapshot' => $price,
        ]);
    }
}
