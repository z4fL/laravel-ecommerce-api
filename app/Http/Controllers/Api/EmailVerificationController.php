<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\http\Requests\VerifyEmailRequest as EmailVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(
                message: "Email address is already verified."
            );
        }

        $user->sendEmailVerificationNotification();

        return $this->success(
            message: "Verification link sent successfully."
        );
    }

    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return $this->success(
                message: "Email address is already verified."
            );
        }

        $request->fulfill();

        return $this->success(
            message: "Email address verified successfully."
        );
    }
}
