<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => fake()->name(),
            'username' => fake()->userName(),
            'email' => 'fadlidzaky3@gmail.com',
            'phone' => fake('id_ID')->e164PhoneNumber(),
            'role' => 'seller',
        ]);

        User::factory()->create([
            'name' => fake()->name(),
            'username' => fake()->userName(),
            'email' => 'zzzakc15@gmail.com',
            'phone' => fake('id_ID')->e164PhoneNumber(),
            'role' => 'customer',
        ]);
    }
}
