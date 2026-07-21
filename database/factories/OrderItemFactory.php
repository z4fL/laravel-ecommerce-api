<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(10_000, 5_000_000);
        $quantity = fake()->numberBetween(1, 10);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_sku' => strtoupper(fake()->bothify('SKU-######')),
            'product_name' => fake()->unique()->sentence(),
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
        ];
    }

    public function fromProduct(Product $product): static
    {
        return $this
            ->for($product)
            ->state([
                'product_sku' => $product->sku,
                'product_name' => $product->name,
                'price' => $product->price,
            ]);
    }
}
