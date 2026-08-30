<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class VerifyEmail extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'api.v1.verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 20)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
