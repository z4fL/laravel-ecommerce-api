<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Enum\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_a_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'username' => 'john',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '085222555111',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'john',
            'role' => UserRole::CUSTOMER->value,
        ]);

        $response->assertJsonPath('data.access_token', fn ($token) => is_string($token));
        $response->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_it_can_login_a_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk();

        $response->assertJsonPath('data.access_token', fn ($token) => is_string($token));
        $response->assertJsonPath('data.token_type', 'Bearer');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'api-token',
        ]);
    }

    public function test_it_rejects_invalid_login_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_can_send_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo(
            $user,
            \App\Notifications\ResetPassword::class
        );
    }

    public function test_it_rejects_nonexistent_email_for_password_reset(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'does-not-exist@example.com',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => null,
            ])
            ->assertJsonPath(
                'errors.email.0',
                "We can't find a user with that email address."
            );

        Notification::assertNothingSent();
    }

    public function test_it_can_reset_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertTrue(
            Hash::check('NewPassword123!', $user->password)
        );
    }

    public function test_password_reset_revokes_all_user_tokens(): void
    {
        $user = User::factory()->create();

        $user->createToken('laptop');
        $user->createToken('phone');
        $user->createToken('tablet');

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_rejects_invalid_password_reset_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertUnprocessable();

        $this->assertFalse(
            Hash::check('NewPassword123!', $user->password)
        );
    }

    public function test_it_cannot_reuse_password_reset_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::createToken($user);

        $payload = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];

        $firstResponse = $this->postJson(
            '/api/v1/auth/reset-password',
            $payload
        );

        $firstResponse->assertOk();

        $secondResponse = $this->postJson(
            '/api/v1/auth/reset-password',
            [
                ...$payload,
                'password' => 'AnotherPassword123!',
                'password_confirmation' => 'AnotherPassword123!',
            ]
        );

        $secondResponse->assertUnprocessable();
    }
}
