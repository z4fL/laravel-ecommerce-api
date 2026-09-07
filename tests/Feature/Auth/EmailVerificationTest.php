<?php

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('sends a verification notification to an unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'Verification link sent successfully.');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not resend verification to a verified user', function () {
    Notification::fake();
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk()
        ->assertJsonPath('message', 'Email address is already verified.');

    Notification::assertNothingSent();
});

it('requires authentication to request email verification', function () {
    $this->postJson('/api/v1/auth/email/verification-notification')
        ->assertUnauthorized();
});

it('verifies an email with a valid signed URL', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('api.v1.verification.verify', now()->addMinute(), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('message', 'Email address verified successfully.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects expired, tampered, and mismatched verification URLs', function (string $case) {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute(
        'api.v1.verification.verify',
        $case === 'expired' ? now()->subMinute() : now()->addMinute(),
        [
            'id' => $user->id,
            'hash' => $case === 'hash' ? sha1('other@example.com') : sha1($user->getEmailForVerification()),
        ]
    );

    if ($case === 'signature') {
        $url .= '&signature=invalid';
    }

    $this->getJson($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['expired', 'signature', 'hash']);

it('handles an already verified email without changing its timestamp', function () {
    $user = User::factory()->create();
    $verifiedAt = $user->email_verified_at;
    $url = URL::temporarySignedRoute('api.v1.verification.verify', now()->addMinute(), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('message', 'Email address is already verified.');

    expect($user->fresh()->email_verified_at->timestamp)->toBe($verifiedAt->timestamp);
});