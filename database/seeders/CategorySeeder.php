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
                'description' => 'Electronic devices and gadgets including computers, smartphones, and accessories.',
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, footwear, bags, and fashion accessories for men and women.',
            ],
            [
                'name' => 'Home & Living',
                'description' => 'Furniture, home decoration, kitchen appliances, and household essentials.',
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
