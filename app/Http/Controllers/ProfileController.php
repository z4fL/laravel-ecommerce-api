<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => $user,
        ], 200);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        $user->fill($validated);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user->fresh(),
        ]);
    }
}
