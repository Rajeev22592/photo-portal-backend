<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;

abstract class Controller extends BaseController
{
    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = ['success' => true];
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message !== null) {
            $response['message'] = $message;
        }
        return response()->json($response, $code);
    }

    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        if (! empty($errors)) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $code);
    }

    protected function planLimitReached(string $message = 'Plan limit reached.', array $details = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => ['code' => ['PLAN_LIMIT_REACHED']],
            ...$details,
        ], 403);
    }
}
