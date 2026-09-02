<?php

use App\Enum\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Category soft delete flow', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'api');
    });

    it('1# soft delete & restore category', function () {
        $category = Category::factory()->create();

        $this->deleteJson('/api/v1/categories/' . $category->slug)
            ->assertOk();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);

        $this->postJson('/api/v1/categories/' . $category->slug . '/restore')
            ->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    });

    it('2# deleted category hidden', function () {
        $category = Category::factory()->create();
        $category->delete();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('3# cannot restore active category', function () {
        $category = Category::factory()->create();

        $this->postJson('/api/v1/categories/' . $category->slug . '/restore')
            ->assertUnprocessable();
    });
});

describe('Tag soft delete flow', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'api');
    });

    it('4# soft delete & restore tag', function () {
        $tag = Tag::factory()->create();

        $this->deleteJson('/api/v1/tags/' . $tag->slug)
            ->assertOk();

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);

        $this->postJson('/api/v1/tags/' . $tag->slug . '/restore')
            ->assertOk();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'deleted_at' => null,
        ]);
    });

    it('5# deleted tag hidden', function () {
        $tag = Tag::factory()->create();
        $tag->delete();

        $this->getJson('/api/v1/tags')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('6# cannot restore active tag', function () {
        $tag = Tag::factory()->create();

        $this->postJson('/api/v1/tags/' . $tag->slug . '/restore')
            ->assertUnprocessable();
    });
});

describe('Product soft delete flow', function () {
    beforeEach(function () {
        $this->seller = User::factory()->seller()->create();
        $this->store = Store::factory()->create([
            'user_id' => $this->seller->id,
        ]);
        $this->actingAs($this->seller, 'api');
    });

    it('7# seller can soft delete & restore own product', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->deleteJson('/api/v1/store/products/' . $product->slug)
            ->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->postJson('/api/v1/store/products/' . $product->slug . '/restore')
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    });

    it('8# seller cannot restore another seller product', function () {
        $otherSeller = User::factory()->seller()->create();
        $otherStore = Store::factory()->create([
            'user_id' => $otherSeller->id,
        ]);
        $product = Product::factory()->create([
            'store_id' => $otherStore->id,
        ]);

        $this->postJson('/api/v1/store/products/' . $product->slug . '/restore')
            ->assertNotFound();
    });

    it('9# deleted product hidden from public and store listing', function () {
        $product = Product::factory()->published()->create([
            'store_id' => $this->store->id,
        ]);
        $product->delete();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/store/products')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('10# cannot restore active product', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->postJson('/api/v1/store/products/' . $product->slug . '/restore')
            ->assertNotFound();
    });

    it('11# product retains deleted category', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);

        $category->delete();

        $loaded = Product::query()
            ->with(['category' => fn ($q) => $q->withTrashed()])
            ->findOrFail($product->id);

        expect($loaded->category)->not->toBeNull()
            ->and($loaded->category->id)->toBe($category->id);
    });

    it('12# product retains deleted tags', function () {
        $tags = Tag::factory()->count(2)->create();
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $product->tags()->sync($tags);

        $tags->each(fn ($tag) => $tag->delete());

        $loaded = Product::query()
            ->with(['tags' => fn ($q) => $q->withTrashed()])
            ->findOrFail($product->id);

        expect($loaded->tags->pluck('id')->sort()->values()->all())
            ->toBe($tags->pluck('id')->sort()->values()->all());
    });

    it('13# cannot create product with deleted category', function () {
        $category = Category::factory()->create();
        $category->delete();

        $this->postJson('/api/v1/store/products', [
            'category_id' => $category->id,
            'sku' => 'SKU-DEL-CAT-1',
            'name' => 'Deleted Category Product',
            'price' => 100000,
            'status' => ProductStatus::PUBLISHED,
            'stock' => 5,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    });

    it('14# cannot update product to deleted category', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);

        $category->delete();

        $this->patchJson('/api/v1/store/products/' . $product->slug, [
            'category_id' => $category->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    });

    it('15# cannot create product with deleted tag', function () {
        $tag = Tag::factory()->create();
        $tag->delete();

        $this->postJson('/api/v1/store/products', [
            'category_id' => Category::factory()->create()->id,
            'sku' => 'SKU-DEL-TAG-1',
            'name' => 'Deleted Tag Product',
            'price' => 150000,
            'status' => ProductStatus::PUBLISHED,
            'stock' => 4,
            'tag_ids' => [$tag->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_ids.0']);
    });

    it('16# cannot update product to deleted tag', function () {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $tag = Tag::factory()->create();
        $tag->delete();

        $this->patchJson('/api/v1/store/products/' . $product->slug, [
            'tag_ids' => [$tag->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_ids.0']);
    });

    it('17# update product without changing deleted category', function () {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'category_id' => $category->id,
        ]);
        $category->delete();

        $this->patchJson('/api/v1/store/products/' . $product->slug, [
            'name' => 'Updated Product Name',
        ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
            'name' => 'Updated Product Name',
        ]);
    });

    it('18# update product without changing deleted tags', function () {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $product->tags()->sync([$tag->id]);
        $tag->delete();

        $this->patchJson('/api/v1/store/products/' . $product->slug, [
            'name' => 'Updated Product Name With Deleted Tag',
        ])
            ->assertOk();

        $this->assertDatabaseHas('product_tag', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
    });
});
