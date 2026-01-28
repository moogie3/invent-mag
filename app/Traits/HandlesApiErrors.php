<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

trait HandlesApiErrors
{
    /**
     * Handle exceptions and return a safe JSON response.
     *
     * @param \Exception $exception
     * @param string $userMessage
     * @param int $statusCode
     * @param array $context
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(
        \Exception $exception,
        string $userMessage = 'An error occurred while processing your request.',
        int $statusCode = 500,
        array $context = []
    ): JsonResponse {
        // Log the full error details for debugging
        Log::error($userMessage, array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->user()?->id,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $context));

        // Return a safe, generic message to the user
        return response()->json([
            'success' => false,
            'message' => $userMessage,
        ], $statusCode);
    }

    /**
     * Handle validation exceptions.
     *
     * @param \Illuminate\Validation\ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleValidationException(\Illuminate\Validation\ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $exception->errors(),
        ], 422);
    }

    /**
     * Handle model not found exceptions.
     *
     * @param \Illuminate\Database\Eloquent\ModelNotFoundException $exception
     * @param string $resourceName
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleModelNotFoundException(
        \Illuminate\Database\Eloquent\ModelNotFoundException $exception,
        string $resourceName = 'Resource'
    ): JsonResponse {
        Log::warning("$resourceName not found", [
            'model' => $exception->getModel(),
            'user_id' => auth()->user()?->id,
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => false,
            'message' => "$resourceName not found.",
        ], 404);
    }

    /**
     * Handle authorization exceptions.
     *
     * @param \Illuminate\Auth\Access\AuthorizationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleAuthorizationException(\Illuminate\Auth\Access\AuthorizationException $exception): JsonResponse
    {
        Log::warning('Unauthorized access attempt', [
            'message' => $exception->getMessage(),
            'user_id' => auth()->user()?->id,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to perform this action.',
        ], 403);
    }
}
