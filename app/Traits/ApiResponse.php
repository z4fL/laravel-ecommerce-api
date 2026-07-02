<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    private const DEFAULT_SUCCESS_MESSAGE = 'Request completed successfully.';
    private const CREATED_MESSAGE = '%s created successfully.';
    private const UPDATED_MESSAGE = '%s updated successfully.';
    private const DELETED_MESSAGE = '%s deleted successfully.';

    private function respond(
        bool $success,
        string $message,
        mixed $data = null,
        int $status = 200,
        array $extra = []
    ): JsonResponse {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            ...$extra
        ], $status);
    }

    protected function success(
        mixed $data = null,
        string $message = self::DEFAULT_SUCCESS_MESSAGE
    ): JsonResponse {
        return $this->respond(
            success: true,
            message: $message,
            data: $data,
        );
    }

    protected function created(
        string $resource,
        mixed $data = null,
        ?string $message = null,
    ): JsonResponse {
        $message ??= sprintf(self::UPDATED_MESSAGE, $resource);
        return $this->respond(
            success: true,
            message: $message,
            data: $data,
            status: 201,
        );
    }

    protected function updated(
        string $resource,
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        $message ??= sprintf(self::UPDATED_MESSAGE, $resource);

        return $this->respond(
            success: true,
            message: $message,
            data: $data,
        );
    }

    protected function deleted(
        string $resource,
        ?string $message = null
    ): JsonResponse {
        $message ??= sprintf(self::DELETED_MESSAGE, $resource);

        return $this->respond(
            success: true,
            message: $message,
        );
    }

    protected function error(
        string $message,
        int $status = 400
    ): JsonResponse {
        return $this->respond(
            success: false,
            message: $message,
            status: $status,
        );
    }
}
