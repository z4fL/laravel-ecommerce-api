<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enum\UserRole;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::CUSTOMER,
            'phone' => $validated['phone']
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->created('User', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->error(
                'Invalid email or password.',
                401
            );
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success(
            [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'Login successful.'
        );
    }

    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return $this->success(message: 'Logout successful.');
    }

    // reset password
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(message: __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(message: __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
