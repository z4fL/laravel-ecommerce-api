<?php

use App\Enum\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->seller()->create();
    $this->store = Store::factory()->create(['user_id' => $this->seller->id]);
    $this->endpoint = '/api/v1/store/products';
    $this->actingAs($this->seller, 'api');
});

function productPayload(array $overrides = []): array
{
    return array_replace([
        'category_id' => Category::factory()->create()->id,
        'sku' => 'SKU-'.fake()->unique()->numerify('######'),
        'name' => 'Gaming Laptop',
        'description' => fake()->sentence(12),
        'price' => 1_500_000,
        'status' => ProductStatus::PUBLISHED->value,
        'stock' => 10,
    ], $overrides);
}

describe('seller product listing', function () {
    it('lists only the authenticated seller products, including drafts', function () {
        $product = Product::factory()->published()->create(['store_id' => $this->store->id]);
        ProductImage::factory()->count(2)->create(['product_id' => $product->id]);
        Product::factory()->draft()->create(['store_id' => $this->store->id]);
        Product::factory()->create();

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonCount(2, 'data.0.images');
    });

    it('includes images in the seller product detail and returns an empty image collection when none exist', function () {
        $withImages = Product::factory()->create(['store_id' => $this->store->id]);
        ProductImage::factory()->count(2)->create(['product_id' => $withImages->id]);
        $withoutImages = Product::factory()->create(['store_id' => $this->store->id]);

        $this->getJson($this->endpoint.'/'.$withImages->slug)
            ->assertOk()
            ->assertJsonCount(2, 'data.images');

        $this->getJson($this->endpoint.'/'.$withoutImages->slug)
            ->assertOk()
            ->assertJsonPath('data.images', []);
    });

    it('supports seller listing search, filters, sorting, and pagination within ownership', function () {
        Product::factory()->create(['store_id' => $this->store->id, 'name' => 'Zebra', 'price' => 2_000]);
        Product::factory()->create(['store_id' => $this->store->id, 'name' => 'Apple', 'price' => 1_000]);
        Product::factory()->create(['name' => 'A Product']);

        $this->getJson($this->endpoint.'?search=Apple&sort=name&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Apple')
            ->assertJsonPath('meta.total', 1);
    });
});

describe('seller product creation and update', function () {
    it('creates a product for the authenticated store and syncs tags', function () {
        $tags = Tag::factory()->count(2)->create();

        $this->postJson($this->endpoint, productPayload(['tag_ids' => $tags->pluck('id')->all()]))
            ->assertCreated()
            ->assertJsonPath('data.name', 'Gaming Laptop');

        $product = Product::query()->latest('id')->firstOrFail();
        expect($product->store_id)->toBe($this->store->id)
            ->and($product->tags()->pluck('tags.id')->sort()->values()->all())
            ->toBe($tags->pluck('id')->sort()->values()->all());
    });

    it('rejects invalid required fields and invalid category or tag relationships', function () {
        $this->postJson($this->endpoint, [])->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'sku', 'name', 'price', 'status', 'stock']);

        $deletedCategory = Category::factory()->create();
        $deletedCategory->delete();
        $deletedTag = Tag::factory()->create();
        $deletedTag->delete();

        $this->postJson($this->endpoint, productPayload([
            'category_id' => $deletedCategory->id,
            'tag_ids' => [$deletedTag->id],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'tag_ids.0']);
    });

    it('updates owned products and preserves tags when tag_ids is omitted', function () {
        $tags = Tag::factory()->count(2)->create();
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $product->tags()->sync($tags);

        $this->patchJson($this->endpoint.'/'.$product->slug, ['name' => 'Updated Product'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Product');

        expect($product->fresh()->tags->pluck('id')->sort()->values()->all())
            ->toBe($tags->pluck('id')->sort()->values()->all());
    });

    it('replaces tags when tag_ids is supplied and rejects invalid updates', function () {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $newTag = Tag::factory()->create();

        $this->patchJson($this->endpoint.'/'.$product->slug, ['tag_ids' => [$newTag->id]])->assertOk();
        expect($product->fresh()->tags->pluck('id')->all())->toBe([$newTag->id]);

        $this->patchJson($this->endpoint.'/'.$product->slug, ['price' => -1])
            ->assertUnprocessable()->assertJsonValidationErrors(['price']);
    });
});

describe('seller product authorization and lifecycle', function () {
    it('requires authentication and seller authorization to create products', function () {
        $this->app['auth']->forgetGuards();
        $this->postJson($this->endpoint, productPayload())->assertUnauthorized();

        $this->actingAs(User::factory()->customer()->create(), 'api')
            ->postJson($this->endpoint, productPayload())->assertForbidden();
    });

    it('hides another seller product from view, update, and delete', function () {
        $product = Product::factory()->create();

        $this->getJson($this->endpoint.'/'.$product->slug)->assertNotFound();
        $this->patchJson($this->endpoint.'/'.$product->slug, ['name' => 'Hacked'])->assertNotFound();
        $this->deleteJson($this->endpoint.'/'.$product->slug)->assertNotFound();
    });

    it('soft deletes and restores an owned product, but cannot restore an active product', function () {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $this->deleteJson($this->endpoint.'/'.$product->slug)->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->postJson($this->endpoint.'/'.$product->slug.'/restore')->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);

        $this->postJson($this->endpoint.'/'.$product->slug.'/restore')->assertNotFound();
    });
});
