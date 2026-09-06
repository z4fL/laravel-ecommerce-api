<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_login_requests_are_not_throttled(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'Password123!',
        ])->assertOk();
    }

    public function test_login_is_limited_by_ip_and_email(): void
    {
        User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $payload = [
            'email' => 'customer@example.com',
            'password' => 'WrongPassword123!',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', $payload)
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', $payload)
            ->assertTooManyRequests();
    }

    public function test_login_identity_buckets_are_isolated_until_ip_limit(): void
    {
        $payload = [
            'password' => 'WrongPassword123!',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/login', [
                ...$payload,
                'email' => 'first@example.com',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            ...$payload,
            'email' => 'second@example.com',
        ])->assertUnauthorized();
    }

    public function test_registration_is_limited_per_ip(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/register', [
                'name' => 'Customer '.$attempt,
                'username' => 'customer'.$attempt,
                'email' => 'customer'.$attempt.'@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'phone' => '085222555'.$attempt,
            ])->assertCreated();
        }

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer 6',
            'username' => 'customer6',
            'email' => 'customer6@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '085222555116',
        ])->assertTooManyRequests();
    }

    public function test_password_reset_requests_are_limited_by_email(): void
    {
        foreach (range(1, 3) as $attempt) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'abused@example.com',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'abused@example.com',
        ])->assertTooManyRequests();
    }

    public function test_email_verification_limit_is_isolated_per_user(): void
    {
        Notification::fake();

        $firstUser = User::factory()->create(['email_verified_at' => null]);
        $secondUser = User::factory()->create(['email_verified_at' => null]);

        $firstToken = $firstUser->createToken('api-token')->plainTextToken;

        foreach (range(1, 3) as $attempt) {
            $this->withToken($firstToken)
                ->postJson('/api/v1/auth/email/verification-notification')
                ->assertOk();
        }

        $this->withToken($firstToken)
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertTooManyRequests();

        $this->app['auth']->forgetGuards();
        $secondToken = $secondUser->createToken('api-token')->plainTextToken;

        $this->withToken($secondToken)
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertOk();

        Notification::assertSentTo($secondUser, VerifyEmail::class);
    }
}