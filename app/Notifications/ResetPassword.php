<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordBase
{
    public function toMail($notifiable): MailMessage
    {
        $url = config('app.frontend_url')
        . '/reset-password?token=' . $this->token
        . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return $this->buildMailMessage($url);
    }
}
