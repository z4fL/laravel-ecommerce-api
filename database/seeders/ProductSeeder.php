<?php

namespace Database\Seeders;

use App\Enum\ProductStatus;
use App\Enum\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seller = User::where('role', UserRole::SELLER)->firstOrFail();

        $electronics = Category::where('slug', 'electronics')->firstOrFail();
        $books = Category::where('slug', 'books')->firstOrFail();

        $gaming = Tag::where('slug', 'gaming')->firstOrFail();
        $wireless = Tag::where('slug', 'wireless')->firstOrFail();
        $rgb = Tag::where('slug', 'rgb')->firstOrFail();
        $mechanical = Tag::where('slug', 'mechanical')->firstOrFail();
        $bluetooth = Tag::where('slug', 'bluetooth')->firstOrFail();
        $programming = Tag::where('slug', 'programming')->firstOrFail();
        $bestseller = Tag::where('slug', 'bestseller')->firstOrFail();
        $ergonomic = Tag::where('slug', 'ergonomic')->firstOrFail();

        // 1
        $keyboard = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $electronics->id,
            'sku' => 'ELC-001',
            'name' => 'Mechanical Keyboard RGB',
            'description' => 'Mechanical keyboard with hot-swappable switches, RGB backlight, and full-size layout for gaming and productivity.',
            'price' => 899000,
            'status' => ProductStatus::PUBLISHED,
        ]);

        $keyboard->tags()->sync([
            $gaming->id,
            $rgb->id,
            $mechanical->id,
        ]);

        // 2
        $mouse = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $electronics->id,
            'sku' => 'ELC-002',
            'name' => 'Wireless Bluetooth Mouse',
            'description' => 'Ergonomic wireless mouse with Bluetooth connectivity, rechargeable battery, and silent click buttons.',
            'price' => 249000,
            'status' => ProductStatus::PUBLISHED,
        ]);

        $mouse->tags()->sync([
            $wireless->id,
            $bluetooth->id,
            $ergonomic->id,
        ]);

        // 3
        $book = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $books->id,
            'sku' => 'BOK-001',
            'name' => 'Clean Code',
            'description' => 'A practical guide to writing clean, maintainable, and readable software by applying professional programming practices.',
            'price' => 315000,
            'status' => ProductStatus::PUBLISHED,
        ]);

        $book->tags()->sync([
            $programming->id,
            $bestseller->id,
        ]);

        // 4 (draft)
        $headphones = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $electronics->id,
            'sku' => 'ELC-003',
            'name' => 'Over-Ear Wireless Headphones',
            'description' => 'Comfortable over-ear headphones with noise isolation and long battery life.',
            'price' => 599000,
            'status' => ProductStatus::DRAFT,
        ]);

        $headphones->tags()->sync([
            $wireless->id,
            $ergonomic->id,
        ]);

    }
}
