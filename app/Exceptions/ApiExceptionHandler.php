<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionHandler
{
    use ApiResponse;

    /**
     * Render the exception as an HTTP response.
     */
    public function handle(Throwable $exception): JsonResponse
    {
        return match (true) {

            // Validation
            $exception instanceof ValidationException
            => $this->validation($exception),

            // Authentication
            // $exception instanceof AuthenticationException
            // => $this->unauthenticated(),

            $exception instanceof TokenExpiredException
            => $this->jwt('Token expired.'),

            $exception instanceof TokenInvalidException
            => $this->jwt('Token invalid.'),

            $exception instanceof JWTException
            => $this->jwt('Authentication token error.'),

            // Fallback
            default
            => $this->exception($exception),
        };
    }

    private function validation(
        ValidationException $exception
    ): JsonResponse {
        return $this->error(
            message: 'Validation failed.',
            status: 422,
            errors: $exception->errors(),
        );
    }

    private function unauthenticated(): JsonResponse
    {
        return $this->error(
            message: 'Unauthenticated.',
            status: 401,
        );
    }

    private function jwt(string $message): JsonResponse
    {
        return $this->error(
            message: $message,
            status: 401,
        );
    }

    private function exception(Throwable $exception): JsonResponse
    {
        $status = match (true) {
            $exception instanceof HttpExceptionInterface
            => $exception->getStatusCode(),

            default => 500,
        };

        return $this->error(
            message: $this->message($exception, $status),
            status: $status,
        );
    }

    private function message(Throwable $exception, int $status): string
    {
        if (app()->hasDebugModeEnabled()) {
            return $exception->getMessage();
        }

        return match ($status) {
            401 => 'Unauthenticated.',
            403 => 'You are not authorized to perform this action.',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            429 => 'Too Many Requests',
            default => $status >= 500
                ? 'Internal Server Error'
                : $exception->getMessage(),
        };
    }
}
