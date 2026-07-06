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
            [
                'name' => 'New Arrival',
            ],
            [
                'name' => 'Best Seller',
            ],
            [
                'name' => 'Featured',
            ],
            [
                'name' => 'Limited Edition',
            ],
            [
                'name' => 'Discount',
            ],
        ];

        foreach ($tags as $tag) {
            Tag::create([
                'name' => $tag['name'],
            ]);
        }
    }
}
