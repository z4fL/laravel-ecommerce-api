<?php

use App\Enum\ProductStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('User soft delete flow', function () {
    it('soft deletes the account and keeps it in the database', function () {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/me')->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        expect(User::withTrashed()->find($user->id))->not->toBeNull();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    });

    it('does not authenticate a soft deleted user', function () {
        $user = User::factory()->create([
            'email' => 'deleted@example.com',
            'password' => Hash::make('Password123!'),
        ]);
        $user->delete();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'deleted@example.com',
            'password' => 'Password123!',
        ])->assertUnauthorized();
    });

    it('excludes soft deleted users from active queries', function () {
        $user = User::factory()->create();
        $user->delete();

        expect(User::query()->find($user->id))->toBeNull()
            ->and(User::withTrashed()->find($user->id))->not->toBeNull();
    });

    it('allows an email to be reused after soft delete but rejects active duplicates', function () {
        $deleted = User::factory()->create(['email' => 'reusable@example.com']);
        $deleted->delete();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Replacement User',
            'username' => 'replacement-user',
            'email' => 'reusable@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '085222555111',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate User',
            'username' => 'duplicate-user',
            'email' => 'reusable@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '085222555112',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    });

    it('rejects restoring a user when an active user owns the email', function () {
        $admin = User::factory()->admin()->create();
        $deleted = User::factory()->create(['email' => 'restore@example.com']);
        $deleted->delete();
        User::factory()->create(['email' => 'restore@example.com']);

        $this->actingAs($admin, 'api')
            ->postJson("/api/v1/users/{$deleted->id}/restore")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('restores a user when the email is available', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $user->delete();

        $this->actingAs($admin, 'api')
            ->postJson("/api/v1/users/{$user->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    });

    it('keeps historical orders and payments after account deletion', function () {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        DB::table('payments')->insert([
            'order_id' => $order->id,
            'gateway' => 'test',
            'gateway_order_id' => 'test-' . $order->id,
            'status' => 'pending',
            'amount' => $order->total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->delete();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id]);
    });
});

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
