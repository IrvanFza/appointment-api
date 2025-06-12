<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{

    protected function sendSuccessResponse(array $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    protected function sendErrorResponse(string $error, array $errorMessages = [], int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    protected function sendValidationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->sendErrorResponse($message, $errors, 422);
    }

    protected function sendUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->sendErrorResponse($message, [], 401);
    }

    protected function sendNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->sendErrorResponse($message, [], 404);
    }
} 