<?php

use App\Enum\StoreStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seller = User::factory()->seller()->create();
    $this->store = Store::factory()->for($this->seller)->create();
    $this->endpoint = '/api/v1/store';
    $this->actingAs($this->seller, 'api');
});

describe('current seller store', function () {
    it('returns the authenticated users store and its products', function () {
        Product::factory()->create(['store_id' => $this->store->id]);

        $this->getJson($this->endpoint)
            ->assertOk()
            ->assertJsonPath('data.name', $this->store->name)
            ->assertJsonCount(1, 'data.products');
    });

    it('returns not found when the authenticated user has no store', function () {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller, 'api')
            ->getJson($this->endpoint)
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'message' => 'Resource not found.',
                'data' => null,
            ]);
    });
});

describe('seller store update', function () {
    it('updates only the authenticated sellers store', function () {
        $otherSeller = User::factory()->seller()->create();
        $otherStore = Store::factory()->for($otherSeller)->create();

        $this->patchJson($this->endpoint, [
            'name' => 'Updated Store',
            'description' => 'A sufficiently descriptive store update.',
            'phone' => '0123456789',
            'status' => StoreStatus::SUSPENDED->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Store');

        expect($this->store->fresh()->name)->toBe('Updated Store')
            ->and($this->store->fresh()->status)->toBe(StoreStatus::ACTIVE)
            ->and($otherStore->fresh()->name)->toBe($otherStore->name);
    });

    it('validates update fields', function () {
        $this->patchJson($this->endpoint, ['name' => 'No'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('cannot update a store when the authenticated user does not own one', function () {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller, 'api')
            ->patchJson($this->endpoint, ['name' => 'Hacked Store'])
            ->assertNotFound();

        expect($this->store->fresh()->name)->not->toBe('Hacked Store');
    });
});

describe('seller store deletion', function () {
    it('deletes the authenticated sellers store and cascades its products', function () {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $this->deleteJson($this->endpoint)
            ->assertOk();

        $this->assertDatabaseMissing('stores', ['id' => $this->store->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('users', ['id' => $this->seller->id]);
    });

    it('returns not found when deleting without a store', function () {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller, 'api')
            ->deleteJson($this->endpoint)
            ->assertNotFound();
    });
});
