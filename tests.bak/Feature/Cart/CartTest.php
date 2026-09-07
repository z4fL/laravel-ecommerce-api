<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->customer = User::factory()
        ->customer()
        ->create();

    $this->endpoint = '/api/v1/cart';
});

describe('GET /cart', function () {

    it('customer can view own cart with items', function () {

        Cart::factory()
            ->for($this->customer)
            ->withItems(3)
            ->create();

        $this->actingAs($this->customer, 'api');

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data.items');
    });

    it('customer only sees own cart items', function () {

        $anotherCustomer = User::factory()
            ->customer()
            ->create();

        Cart::factory()
            ->for($this->customer)
            ->withItems(3)
            ->create();

        Cart::factory()
            ->for($anotherCustomer)
            ->withItems(2)
            ->create();

        $this->actingAs($this->customer, 'api');

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data.items');
    });

    it('customer gets empty cart when no cart exists', function () {

        $this->actingAs($this->customer, 'api');

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(0, 'data.items');
    });

    it('guest cannot view cart', function () {

        $this->getJson($this->endpoint)
            ->assertUnauthorized();
    });
});

describe('DELETE /cart', function () {

    it('customer can clear own cart', function () {

        $cart = Cart::factory()
            ->for($this->customer)
            ->withItems(3)
            ->create();

        $this->actingAs($this->customer, 'api');

        $this->deleteJson($this->endpoint)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect($cart->fresh()->cartItems)
            ->toHaveCount(0);

        $this->assertDatabaseCount('cart_items', 0);
    });

    it('guest cannot clear cart', function () {

        $this->deleteJson($this->endpoint)
            ->assertUnauthorized();
    });

    it('cart record still exists after clear cart', function () {

        $cart = Cart::factory()
            ->for($this->customer)
            ->withItems(2)
            ->create();

        $this->actingAs($this->customer, 'api');

        $this->deleteJson($this->endpoint)
            ->assertOk();

        expect(Cart::find($cart->id))
            ->not()
            ->toBeNull();

        expect($cart->fresh()->cartItems)
            ->toHaveCount(0);
    });
});


describe('POST /cart/items/{public_product}', function () {

    beforeEach(function () {
        $this->endpoint = fn(Product $product) => "/api/v1/cart/items/{$product->slug}";

        $this->actingAs($this->customer, 'api');
    });

    it('customer can add product to cart', function () {

        $product = Product::factory()->create();

        $this->postJson(($this->endpoint)($product), [
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->customer->id,
        ]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price_snapshot' => $product->price,
        ]);
    });

    it('customer can add another product to cart', function () {

        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $this->postJson(($this->endpoint)($first), [
            'quantity' => 2,
        ])->assertCreated();

        $this->postJson(($this->endpoint)($second), [
            'quantity' => 3,
        ])->assertCreated();

        $cart = Cart::first();

        expect($cart->cartItems)
            ->toHaveCount(2);
    });

    it('customer can increment quantity of existing cart item', function () {

        $product = Product::factory()->create();

        $cart = Cart::factory()
            ->for($this->customer)
            ->create();

        CartItem::factory()
            ->for($cart)
            ->for($product)
            ->quantity(2)
            ->snapshot($product->price)
            ->create();

        $this->postJson(($this->endpoint)($product), [
            'quantity' => 3,
        ])->assertCreated();

        $item = $cart->fresh()
            ->cartItems()
            ->where('product_id', $product->id)
            ->first();

        expect($item->quantity)->toBe(5);

        $this->assertDatabaseCount('cart_items', 1);
    });

    it('cart is created automatically when it does not exist', function () {

        $product = Product::factory()->create();

        expect($this->customer->cart)->toBeNull();

        $this->postJson(($this->endpoint)($product), [
            'quantity' => 1,
        ])->assertCreated();

        expect($this->customer->fresh()->cart)
            ->not()
            ->toBeNull();
    });

    it('customer cannot add own product to cart', function () {

        $seller = User::factory()->seller()->create();

        $store = Store::factory()->create([
            'user_id' => $seller->id,
        ]);

        $product = Product::factory()->create([
            'store_id' => $store->id,
        ]);

        $this->actingAs($seller, 'api');

        $this->postJson("/api/v1/cart/items/{$product->slug}", [
            'quantity' => 1,
        ])
            ->assertForbidden();
    });

    it('customer cannot add draft product', function () {

        $product = Product::factory()
            ->draft()
            ->create();

        $this->postJson("/api/v1/cart/items/{$product->slug}", [
            'quantity' => 1,
        ])
            ->assertNotFound();
    });

    it('customer cannot add non existing product', function () {

        $this->postJson('/api/v1/cart/items/not-found-product', [
            'quantity' => 1,
        ])
            ->assertNotFound();
    });

    it('guest cannot add product to cart', function () {

        auth()->logout();

        $product = Product::factory()->create();

        $this->postJson("/api/v1/cart/items/{$product->slug}", [
            'quantity' => 1,
        ])
            ->assertUnauthorized();
    });
});

describe('PATCH /cart/items/{item}', function () {

    beforeEach(function () {
        $this->endpoint = '/api/v1/cart/items';

        $this->actingAs($this->customer, 'api');
    });

    it('customer can update own cart item quantity', function () {

        $cart = Cart::factory()
            ->for($this->customer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $this->patchJson("{$this->endpoint}/{$item->id}", [
            'quantity' => 5,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect($item->fresh()->quantity)
            ->toBe(5);
    });

    it('customer cannot update another customer cart item', function () {

        $anotherCustomer = User::factory()->customer()->create();

        $cart = Cart::factory()
            ->for($anotherCustomer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $this->patchJson("{$this->endpoint}/{$item->id}", [
            'quantity' => 5,
        ])
            ->assertForbidden();
    });

    it('admin can update any cart item', function () {

        $customer = User::factory()->customer()->create();

        $cart = Cart::factory()
            ->for($customer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api');

        $this->patchJson("{$this->endpoint}/{$item->id}", [
            'quantity' => 10,
        ])
            ->assertOk();

        expect($item->fresh()->quantity)
            ->toBe(10);
    });

    it('guest cannot update cart item', function () {

        auth()->logout();

        $item = CartItem::factory()->create();

        $this->patchJson("{$this->endpoint}/{$item->id}", [
            'quantity' => 5,
        ])
            ->assertUnauthorized();
    });

    it('quantity must be at least one', function () {

        $cart = Cart::factory()
            ->for($this->customer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $this->patchJson("{$this->endpoint}/{$item->id}", [
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'quantity',
            ]);
    });
});

describe('DELETE /cart/items/{item}', function () {

    beforeEach(function () {
        $this->endpoint = '/api/v1/cart/items';

        $this->actingAs($this->customer, 'api');
    });

    it('customer can remove own cart item', function () {

        $cart = Cart::factory()
            ->for($this->customer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $this->deleteJson("{$this->endpoint}/{$item->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    });

    it('customer cannot remove another customer cart item', function () {

        $anotherCustomer = User::factory()->customer()->create();

        $cart = Cart::factory()
            ->for($anotherCustomer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $this->deleteJson("{$this->endpoint}/{$item->id}")
            ->assertForbidden();
    });

    it('admin can remove any cart item', function () {

        $customer = User::factory()->customer()->create();

        $cart = Cart::factory()
            ->for($customer)
            ->withItems()
            ->create();

        $item = $cart->cartItems()->first();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'api');

        $this->deleteJson("{$this->endpoint}/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', [
            'id' => $item->id,
        ]);
    });

    it('guest cannot remove cart item', function () {

        auth()->logout();

        $item = CartItem::factory()->create();

        $this->deleteJson("{$this->endpoint}/{$item->id}")
            ->assertUnauthorized();
    });
});
