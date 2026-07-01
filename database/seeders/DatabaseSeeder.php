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
            'email' => 'fadlidzaky3@gmail.com',
            'role' => 'seller',
        ]);

        User::factory()->create([
            'name' => fake()->name(),
            'email' => 'zzzakc15@gmail.com',
            'role' => 'customer',
        ]);
    }
}
