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
use OpenApi\Attributes as OA;


class AuthController extends Controller
{

    #[OA\Post(
        path: '/auth/register',
        operationId: 'register',
        summary: 'Register a new user',
        description: 'Register a new customer account and return the authenticated user with a JWT access token.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'User registration data',
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'John Doe',
                        maxLength: 255
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'john@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        minLength: 8,
                        example: 'password123'
                    ),
                    new OA\Property(
                        property: 'password_confirmation',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'User registered successfully'
                        ),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'user',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                                        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                                        new OA\Property(property: 'role', type: 'string', example: 'customer'),
                                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time')
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'token',
                                    type: 'string',
                                    example: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'
                                ),
                                new OA\Property(
                                    property: 'token_type',
                                    type: 'string',
                                    example: 'Bearer'
                                ),
                                new OA\Property(
                                    property: 'expires_in',
                                    type: 'integer',
                                    example: 3600
                                ),
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),

            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: false
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Validation failed'
                        ),
                        new OA\Property(
                            property: 'errors',
                            properties: [
                                new OA\Property(
                                    property: 'email',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'string',
                                        example: 'The email has already been taken.'
                                    )
                                )
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            )
        ],
        security: []
    )]
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
