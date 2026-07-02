<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler extends Exception
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
            $exception instanceof AuthenticationException
            => $this->unauthenticated(),

            // Authorization
            $exception instanceof AccessDeniedHttpException
            => $this->forbidden(),

            // Eloquent
            $exception instanceof NotFoundHttpException &&
                $exception->getPrevious() instanceof ModelNotFoundException
            => $this->modelNotFound(),

            // Route
            $exception instanceof NotFoundHttpException
            => $this->routeNotFound(),

            // HTTP Method
            $exception instanceof MethodNotAllowedHttpException
            => $this->methodNotAllowed(),

            // JWT
            $exception instanceof TokenExpiredException
            => $this->tokenExpired(),

            $exception instanceof TokenInvalidException
            => $this->tokenInvalid(),

            $exception instanceof JWTException
            => $this->tokenMissing(),

            // Fallback
            default
            => $this->internalServerError($exception),
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

    private function forbidden(): JsonResponse
    {
        return $this->error(
            message: 'You are not authorized to perform this action.',
            status: 403,
        );
    }

    private function modelNotFound(): JsonResponse
    {
        return $this->error(
            message: 'Resource not found.',
            status: 404,
        );
    }

    private function routeNotFound(): JsonResponse
    {
        return $this->error(
            message: 'Route not found.',
            status: 404,
        );
    }

    private function methodNotAllowed(): JsonResponse
    {
        return $this->error(
            message: 'Method not allowed.',
            status: 405,
        );
    }

    private function tokenExpired(): JsonResponse
    {
        return $this->error(
            message: 'Token expired.',
            status: 401,
        );
    }

    private function tokenInvalid(): JsonResponse
    {
        return $this->error(
            message: 'Token invalid.',
            status: 401,
        );
    }

    private function tokenMissing(): JsonResponse
    {
        return $this->error(
            message: 'Token missing.',
            status: 401,
        );
    }

    private function internalServerError(Throwable $exception): JsonResponse
    {
        return $this->error(
            message: config('app.debug') ? $exception->getMessage() : 'Internal server error.',
            status: 500,
        );
    }
}
