<?php

namespace Database\Factories;

use App\Enum\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->sentence();

        return [
            'store_id' => Store::factory()->active(),
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->bothify('SKU-######')),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(10_000, 5_000_000),
            'status' => ProductStatus::PUBLISHED,
            'stock' => fake()->numberBetween(1, 100),
        ];
    }

    public function published(): static
    {
        return $this->state(fn() => [
            'status' => ProductStatus::PUBLISHED,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn() => [
            'status' => ProductStatus::DRAFT,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn() => [
            'stock' => fake()->numberBetween(1, 100),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn() => [
            'stock' => 0,
        ]);
    }
}
