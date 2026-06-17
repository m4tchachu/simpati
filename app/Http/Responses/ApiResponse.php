<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success($message = 'Success', $data = null, $statusCode = 200, $extra = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json(array_merge($response, $extra), $statusCode);
    }

    /**
     * Error response
     */
    public static function error($message = 'Error', $error = null, $statusCode = 400, $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($error !== null) {
            $response['error'] = $error;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Paginated response
     */
    public static function paginated($message, $data, $paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 200);
    }

    /**
     * Not found response
     */
    public static function notFound($message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 'NOT_FOUND', 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 'UNAUTHORIZED', 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden($message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 'FORBIDDEN', 403);
    }

    /**
     * Validation error response
     */
    public static function validationError($message = 'Validation failed', $errors = []): JsonResponse
    {
        return self::error($message, 'VALIDATION_ERROR', 422, $errors);
    }
}
