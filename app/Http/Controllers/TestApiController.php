<?php

namespace App\Http\Controllers;

use App\Data\TestApiResponseData;

class TestApiController extends Controller
{
    public function __invoke(): TestApiResponseData
    {
        return TestApiResponseData::from([
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
