<?php

namespace Database\Factories;

use App\Enum\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => sprintf(
                'ORD-%s-%s',
                now()->format('Ymd'),
                fake()->unique()->regexify('[A-Z0-9]{6}')
            ),
            'user_id' => User::factory()->customer(),
            'status' => OrderStatus::PENDING_PAYMENT,

            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'province' => fake()->state(),
            'city' => fake()->city(),
            'district' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'address' => fake()->address(),

            'subtotal' => fake()->numberBetween(10_000, 5_000_000),
            'total' => fake()->numberBetween(10_000, 5_000_000),
        ];
    }
}
