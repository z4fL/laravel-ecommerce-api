<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseRequest;

class VerifyEmailRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = User::findOrFail($this->route('id'));

        return hash_equals(
            (string) $this->route('hash'),
            sha1($user->getEmailForVerification())
        );
    }

    public function fulfill()
    {
        $user = User::findOrFail($this->route('id'));

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
