<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/' . fake()->uuid() . '.jpg',
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }

    public function image(): static
    {
        return $this->state(fn() => [
            'path' => 'products/' . fake()->uuid() . '.jpg',
        ]);
    }
}
