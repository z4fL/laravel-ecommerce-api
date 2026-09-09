<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth('api')->user();

        return $this->success($user);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $user->fill($validated);
        $user->save();

        return $this->updated('User', $user->fresh());
    }

    public function destroy(): JsonResponse
    {
        $user = request()->user();

        $user->tokens()->delete();
        $user->delete();

        return $this->deleted('User');
    }

    public function restore(User $user): JsonResponse
    {
        if (! $user->trashed()) {
            throw ValidationException::withMessages([
                'user' => ['User is already active and cannot be restored.'],
            ]);
        }

        if (User::query()->where('email', $user->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $user->restore();

        return $this->restored('User');
    }
}
