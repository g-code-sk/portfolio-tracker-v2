<?php

use App\Http\Controllers\TestApiController;
use Domain\Register\Controller\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/test-data', TestApiController::class);
Route::post('/register', RegisterController::class);
