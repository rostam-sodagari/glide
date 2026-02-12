<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response with data.
     */
    protected function success($data = [], int $code = 200, array $headers = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Success',
        ], $code, $headers);
    }

    /**
     * Error response.
     */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            ...(count($errors) ? ['errors' => $errors] : []),
        ], $code);
    }

    /**
     * Validation error helper.
     */
    protected function validationError(array $errors): JsonResponse
    {
        return $this->error('Validation failed', 422, $errors);
    }
}
