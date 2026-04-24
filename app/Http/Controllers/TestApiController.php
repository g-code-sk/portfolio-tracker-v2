<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class TestApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Backend API is connected.',
            'timestamp' => now()->toIso8601String(),
            'items' => [
                ['id' => 1, 'name' => 'BTC', 'price' => 65000],
                ['id' => 2, 'name' => 'ETH', 'price' => 3200],
                ['id' => 3, 'name' => 'SOL', 'price' => 150],
            ],
        ]);
    }
}
