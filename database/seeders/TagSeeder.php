<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Gaming'],
            ['name' => 'Wireless'],
            ['name' => 'RGB'],
            ['name' => 'Mechanical'],
            ['name' => 'Bluetooth'],
            ['name' => 'Programming'],
            ['name' => 'Bestseller'],
            ['name' => 'Casual'],
            ['name' => 'Cotton'],
            ['name' => 'Waterproof'],
            ['name' => 'Portable'],
            ['name' => 'Ergonomic'],
        ];

        foreach ($tags as $tag) {
            Tag::create([
                'name' => $tag['name'],
            ]);
        }
    }
}
