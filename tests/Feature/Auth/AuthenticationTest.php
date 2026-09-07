<?php

use App\Enum\UserRole;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('registers a customer and returns a Sanctum token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'username' => 'john-doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'phone' => '085222555111',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'john@example.com')
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.access_token', fn ($token) => is_string($token));

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'username' => 'john-doe',
        'role' => UserRole::CUSTOMER->value,
    ]);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

it('rejects invalid registration data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'username' => 'x',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['name', 'username', 'email', 'password', 'phone']]);
    $this->assertDatabaseCount('users', 0);
});

it('rejects duplicate registration identifiers', function () {
    User::factory()->create([
        'username' => 'john-doe',
        'email' => 'john@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Another User',
        'username' => 'john-doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'phone' => '085222555111',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['username', 'email']]);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => Hash::make('Password123!'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.access_token', fn ($token) => is_string($token));
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'api-token',
    ]);
});

it('rejects invalid login credentials without issuing a token', function () {
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'WrongPassword123!',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'Invalid email or password.');
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('validates login credentials before authentication', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['email', 'password']]);
});

it('logs out and revokes the current Sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

    $response->assertOk()->assertJsonPath('message', 'Logout successful.');
    $this->assertDatabaseCount('personal_access_tokens', 0);

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
});

it('sends a password reset notification for an existing email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'john@example.com']);

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not reveal whether a missing reset email exists', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'missing@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.email.0', "We can't find a user with that email address.");
    Notification::assertNothingSent();
});

it('resets a password, invalidates tokens, and rejects token reuse', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);
    $user->createToken('laptop');
    $token = Password::createToken($user);
    $payload = [
        'email' => $user->email,
        'token' => $token,
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ];

    $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->postJson('/api/v1/auth/reset-password', [
        ...$payload,
        'password' => 'AnotherPassword123!',
        'password_confirmation' => 'AnotherPassword123!',
    ])->assertUnprocessable();
});

it('rejects an invalid password reset token', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword123!')]);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => 'invalid-token',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertUnprocessable();

    expect(Hash::check('OldPassword123!', $user->fresh()->password))->toBeTrue();
});