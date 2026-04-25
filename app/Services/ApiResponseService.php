<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Data;

class ApiResponseService
{
    public function make(string $message, int $status = 200, ?Data $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
