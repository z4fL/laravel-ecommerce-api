<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->endpoint = '/api/v1/products';
});

describe('product catalog', function () {
    it('returns only published products with their public relationships', function () {
        $published = Product::factory()->published()->create();
        $images = ProductImage::factory()->count(2)->create(['product_id' => $published->id]);
        Product::factory()->draft()->create();
        $tag = Tag::factory()->create();
        $published->tags()->attach($tag);

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id)
            ->assertJsonPath('data.0.status', 'published')
            ->assertJsonPath('data.0.category.id', $published->category_id)
            ->assertJsonPath('data.0.tags.0.id', $tag->id)
            ->assertJsonCount(2, 'data.0.images')
            ->assertJsonPath('data.0.images.0.id', $images[0]->id)
            ->assertJsonStructure(['data', 'meta', 'links']);
    });

    it('includes images in the product detail and returns an empty image collection when none exist', function () {
        $withImages = Product::factory()->published()->create();
        ProductImage::factory()->count(2)->create(['product_id' => $withImages->id]);
        $withoutImages = Product::factory()->published()->create();

        $this->getJson($this->endpoint.'/'.$withImages->slug)
            ->assertOk()
            ->assertJsonCount(2, 'data.images');

        $this->getJson($this->endpoint.'/'.$withoutImages->slug)
            ->assertOk()
            ->assertJsonPath('data.images', []);
    });

    it('supports search, category filtering, price filtering, stock filtering, and sorting', function () {
        $category = Category::factory()->create(['name' => 'Computers']);
        Product::factory()->published()->create([
            'category_id' => $category->id,
            'name' => 'Gaming Laptop',
            'price' => 2_000,
            'stock' => 5,
        ]);
        Product::factory()->published()->create([
            'category_id' => $category->id,
            'name' => 'Office Laptop',
            'price' => 1_000,
            'stock' => 0,
        ]);

        $this->getJson($this->endpoint.'?search=Gaming&category='.$category->slug.'&min_price=1500&in_stock=1&sort=name')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaming Laptop');
    });

    it('returns validation errors for invalid catalog parameters', function () {
        $this->getJson($this->endpoint.'?min_price=100&max_price=10&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price', 'per_page']);
    });

    it('supports pagination', function () {
        Product::factory()->published()->count(2)->create();

        $this->getJson($this->endpoint.'?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 2);
    });
});

describe('public product detail', function () {
    it('allows guests and authenticated customers to view published products', function () {
        $product = Product::factory()->published()->create();

        $this->getJson($this->endpoint.'/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug);

        $this->actingAs(User::factory()->customer()->create(), 'api')
            ->getJson($this->endpoint.'/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    });

    it('does not expose draft, deleted, or unknown products', function () {
        $draft = Product::factory()->draft()->create();
        $deleted = Product::factory()->published()->create();
        $deleted->delete();

        $this->getJson($this->endpoint.'/'.$draft->slug)->assertNotFound();
        $this->getJson($this->endpoint.'/'.$deleted->slug)->assertNotFound();
        $this->getJson($this->endpoint.'/missing-product')->assertNotFound();
    });

    it('includes soft-deleted category and tags for an existing product', function () {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $product = Product::factory()->published()->create(['category_id' => $category->id]);
        $product->tags()->attach($tag);
        $category->delete();
        $tag->delete();

        $this->getJson($this->endpoint.'/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.tags.0.id', $tag->id);
    });
});
