<?php

namespace Database\Seeders;

use App\Enum\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::updateOrCreate([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'zaaaafl654@gmail.com',
            'phone' => fake('id_ID')->e164PhoneNumber(),
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        User::factory()->create([
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => 'fadlidzaky3@gmail.com',
            'phone' => fake('id_ID')->e164PhoneNumber(),
            'role' => UserRole::SELLER,
        ]);

        User::factory()->create([
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => 'zzzakc15@gmail.com',
            'phone' => fake('id_ID')->e164PhoneNumber(),
            'role' => UserRole::CUSTOMER,
        ]);

        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
