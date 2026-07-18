<?php

use App\Enum\AddressLabel;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->payload = [
        'recipient_name' => 'John Doe',
        'phone' => '081234567890',
        'label' => AddressLabel::RUMAH->value,
        'province' => 'Jawa Tengah',
        'city' => 'Purbalingga',
        'district' => 'Kalimanah',
        'postal_code' => '53371',
        'address' => 'Jl. Ahmad Yani No. 1',
    ];

    $this->invalidPayload = [
        'recipient_name' => '',
        'phone' => '',
        'province' => '',
        'city' => '',
        'district' => '',
        'postal_code' => '',
        'address' => '',
    ];

    $this->endpoint = '/api/v1/shipping-addresses';
});

function createAddress(User $user, array $payload = [])
{
    return test()
        ->actingAs($user)
        ->postJson(
            test()->endpoint,
            array_merge(test()->payload, $payload)
        );
}

describe('GET /shipping-addresses', function () {

    it('customer can list own shipping addresses', function () {
        $customer = User::factory()->customer()->create();

        ShippingAddress::factory()
            ->count(3)
            ->for($customer)
            ->create();

        $this->actingAs($customer)
            ->getJson('/api/v1/shipping-addresses')
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    });

    it('seller can list own shipping addresses', function () {
        $seller = User::factory()->seller()->create();

        ShippingAddress::factory()
            ->count(2)
            ->for($seller)
            ->create();

        $this->actingAs($seller)
            ->getJson('/api/v1/shipping-addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('does not return other user shipping addresses', function () {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $addresses = ShippingAddress::factory()
            ->count(2)
            ->for($customer)
            ->create();

        ShippingAddress::factory()
            ->count(3)
            ->for($other)
            ->create();

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/shipping-addresses')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        expect(
            collect($response->json('data'))
                ->pluck('id')
                ->sort()
                ->values()
                ->all()
        )->toEqual(
            $addresses
                ->pluck('id')
                ->sort()
                ->values()
                ->all()
        );
    });

    it('returns empty list when user has no shipping addresses', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->getJson('/api/v1/shipping-addresses')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    });
});

describe('POST /shipping-addresses', function () {
    it('customer can create shipping address', function () {
        $customer = User::factory()->customer()->create();

        createAddress($customer)
            ->assertCreated()
            ->assertJson([
                'success' => true,
            ]);

        expect(
            $customer->addresses()->count()
        )->toBe(1);
    });

    it('seller can create shipping address', function () {
        $seller = User::factory()->seller()->create();

        createAddress($seller)
            ->assertCreated();

        expect(
            $seller->addresses()->count()
        )->toBe(1);
    });

    it('first shipping address becomes default', function () {
        $customer = User::factory()->customer()->create();

        createAddress($customer);

        expect(
            $customer->addresses()->first()->is_default
        )->toBeTrue();
    });

    it('can create non default shipping address', function () {
        $customer = User::factory()->customer()->create();

        ShippingAddress::factory()
            ->default()
            ->for($customer)
            ->create();

        $response = createAddress($customer, [
            'is_default' => false,
        ]);

        $newAddress = ShippingAddress::find(
            $response->json('data.id')
        );

        expect(
            $customer->addresses()->where('is_default', true)->count()
        )->toBe(1);

        expect($newAddress->is_default)->toBeFalse();
    });

    it('creating default shipping address replaces previous default', function () {
        $customer = User::factory()->customer()->create();

        ShippingAddress::factory()
            ->default()
            ->for($customer)
            ->create();

        $response = createAddress($customer, [
            'is_default' => true,
        ]);

        $newAddress = ShippingAddress::find(
            $response->json('data.id')
        );

        expect(
            $customer->addresses()->where('is_default', true)->count()
        )->toBe(1);

        expect(
            $newAddress->is_default
        )->toBeTrue();
    });

    it('validates required fields', function () {
        $customer = User::factory()->customer()->create();

        createAddress($customer, $this->invalidPayload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'recipient_name',
                'phone',
                'province',
                'city',
                'district',
                'postal_code',
                'address',
            ]);
    });

    it('guest cannot create shipping address', function () {
        $this->postJson($this->endpoint, $this->payload)
            ->assertUnauthorized();
    });
});

describe('GET /shipping-addresses/{shipping_address}', function () {

    it('user can view own shipping address', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->getJson("/api/v1/shipping-addresses/{$address->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $address->id,
                ],
            ]);
    });

    it('cannot view another user shipping address', function () {
        // $this->withoutExceptionHandling();

        $user = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($other)
            ->create();

        $this->actingAs($user)
            ->getJson("/api/v1/shipping-addresses/{$address->id}")
            ->assertNotFound();
        // ->dump();
        // $response->dd();
    });

    it('returns not found when shipping address does not exist', function () {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/shipping-addresses/999999')
            ->assertNotFound();
    });
});

describe('PATCH /shipping-addresses/{shipping_address}', function () {

    it('user can update own shipping address', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}", [
                'recipient_name' => 'Jane Doe',
                'city' => 'Jakarta',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect($address->fresh())
            ->recipient_name->toBe('Jane Doe')
            ->city->toBe('Jakarta');
    });

    it('validates update request', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}", [
                'phone' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'phone',
            ]);
    });

    it('cannot update another user shipping address', function () {
        $user = User::factory()->customer()->create();

        $other = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($other)
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}", [
                'city' => 'Jakarta',
            ])
            ->assertNotFound();
    });
});

describe('PATCH /shipping-addresses/{shipping_address}/default', function () {

    it('user can change default shipping address', function () {
        $user = User::factory()->customer()->create();

        $previousDefault = ShippingAddress::factory()
            ->for($user)
            ->default()
            ->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}/default")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect($address->fresh()->is_default)->toBeTrue();

        expect($previousDefault->fresh()->is_default)->toBeFalse();

        expect(
            $user->addresses()
                ->where('is_default', true)
                ->sole()
                ->is($address->fresh())
        )->toBeTrue();
    });

    it('does nothing when selected address is already default', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->default()
            ->create();

        $updatedAt = $address->updated_at;
        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}/default")
            ->assertNoContent();

        expect(
            $user->addresses()
                ->where('is_default', true)
                ->sole()
                ->is($address->fresh())
        )->toBeTrue();

        expect($address->fresh()->updated_at)
            ->toEqual($updatedAt);
    });

    it('cannot change another user shipping address as default', function () {
        $user = User::factory()->customer()->create();

        $other = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($other)
            ->default()
            ->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/shipping-addresses/{$address->id}/default")
            ->assertNotFound();
    });
});

describe('DELETE /shipping-addresses/{shipping_address}', function () {

    it('user can delete own shipping address', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/shipping-addresses/{$address->id}")
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        expect(
            ShippingAddress::query()->find($address->id)
        )->toBeNull();
    });

    it('deleting default shipping address assigns another default', function () {
        $user = User::factory()->customer()->create();

        $default = ShippingAddress::factory()
            ->for($user)
            ->default()
            ->create();

        $next = ShippingAddress::factory()
            ->for($user)
            ->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/shipping-addresses/{$default->id}")
            ->assertOk();

        expect(
            ShippingAddress::query()->find($default->id)
        )->toBeNull();

        expect($next->fresh()->is_default)->toBeTrue();

        expect(
            $user->addresses()
                ->where('is_default', true)
                ->sole()
                ->is($next->fresh())
        )->toBeTrue();
    });

    it('can delete last remaining shipping address', function () {
        $user = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($user)
            ->default()
            ->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/shipping-addresses/{$address->id}")
            ->assertOk();

        expect(
            $user->addresses()->exists()
        )->toBeFalse();
    });

    it('cannot delete another user shipping address', function () {
        $user = User::factory()->customer()->create();

        $other = User::factory()->customer()->create();

        $address = ShippingAddress::factory()
            ->for($other)
            ->create();

        $this->actingAs($user)
            ->deleteJson("/api/v1/shipping-addresses/{$address->id}")
            ->assertNotFound();

        expect(
            ShippingAddress::query()->find($address->id)
        )->not->toBeNull();
    });
});
