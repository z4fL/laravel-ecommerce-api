<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects unauthenticated access to protected API routes', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('authenticates protected API requests with a Sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('rejects a revoked or malformed Sanctum token', function () {
    $this->withToken('not-a-valid-token')
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});

it('blocks unverified users from verified routes', function () {
    $user = User::factory()->unverified()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/store')
        ->assertForbidden();
});

it('blocks non-admin users from admin routes', function () {
    $user = User::factory()->seller()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/categories', ['name' => 'Restricted'])
        ->assertForbidden();
});