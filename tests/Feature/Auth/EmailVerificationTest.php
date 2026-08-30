<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_send_verification_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/email/verification-notification');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Verification link sent successfully.',
            ]);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    public function test_it_does_not_send_verification_notification_to_verified_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/auth/email/verification-notification');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email address is already verified.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_it_requires_authentication_to_send_verification_notification(): void
    {
        $response = $this->postJson(
            '/api/v1/auth/email/verification-notification'
        );

        $response->assertUnauthorized();
    }

    public function test_it_can_verify_email_with_valid_verification_url(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(20),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($url);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email address verified successfully.',
            ]);

        $this->assertNotNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_it_handles_already_verified_email(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verifiedAt = $user->email_verified_at;

        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(20),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($url);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email address is already verified.',
            ]);

        $this->assertEquals(
            $verifiedAt->timestamp,
            $user->fresh()->email_verified_at->timestamp
        );
    }

    public function test_it_rejects_invalid_signature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(20),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $url .= '&signature=invalid';

        $response = $this->getJson($url);

        $response->assertForbidden();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_it_rejects_expired_verification_url(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->subMinute(),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->getJson($url);

        $response->assertForbidden();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_it_rejects_invalid_email_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(20),
            [
                'id' => $user->id,
                'hash' => sha1('different@example.com'),
            ]
        );

        $response = $this->getJson($url);

        $response->assertForbidden();

        $this->assertNull(
            $user->fresh()->email_verified_at
        );
    }

    public function test_it_rejects_verification_for_non_existent_user(): void
    {
        $url = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            now()->addMinutes(20),
            [
                'id' => 999999,
                'hash' => sha1('nonexistent@example.com'),
            ]
        );

        $response = $this->getJson($url);

        $response->assertNotFound();
    }

    public function test_unverified_user_cannot_access_verified_feature(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/orders');

        $response->assertForbidden();
    }

    public function test_verified_user_can_pass_verified_middleware(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/cart');

        $this->assertNotSame(403, $response->status());
    }
}
