<?php

namespace Database\Factories;

use App\Enum\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'user_id' => User::factory()->seller(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'phone' => fake()->phoneNumber(),
            'status' => StoreStatus::ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn() => [
            'status' => StoreStatus::ACTIVE,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn() => [
            'status' => StoreStatus::PENDING,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => [
            'status' => StoreStatus::SUSPENDED,
        ]);
    }
}
