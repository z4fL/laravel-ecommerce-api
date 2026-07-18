<?php

namespace Database\Factories;

use App\Enum\AddressLabel;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingAddress>
 */
class ShippingAddressFactory extends Factory
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
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'label' => AddressLabel::RUMAH,
            'province' => fake()->state(),
            'city' => fake()->city(),
            // 'district' => fake()->district(),
            'district' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'address' => fake()->address(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn() => [
            'is_default' => true,
        ]);
    }

    public function nonDefault(): static
    {
        return $this->state([
            'is_default' => false,
        ]);
    }
}
