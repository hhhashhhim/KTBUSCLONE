<?php

namespace App\Http\Controllers\Mobile\Concerns;

use Throwable;

trait RespondsWithMobileApi
{
    protected function success($data, string $message = 'Request completed successfully.', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    protected function failure(string $message, array $errors = [], int $status = 422)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => (object) $errors,
        ], $status);
    }

    protected function exceptionStatus(Throwable $exception, int $fallback = 500): int
    {
        $code = $exception->getCode();

        return is_int($code) && $code >= 400 && $code <= 599
            ? $code
            : $fallback;
    }
}
