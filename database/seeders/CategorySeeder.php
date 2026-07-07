<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Gadgets, devices, and electronic accessories.'
            ],
            [
                'name' => 'Books',
                'description' => 'Printed and digital books across all genres.'
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, footwear, and fashion accessories.'
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Essentials for home improvement, cooking, and dining.'
            ],
            [
                'name' => 'Sports',
                'description' => 'Gear and equipment for fitness and outdoor activities.'
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'description' => $category['description'],
            ]);
        }
    }
}
