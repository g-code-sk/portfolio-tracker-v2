<?php

use App\Http\Controllers\TestApiController;
use Domain\Auth\Controller\LogoutController;
use Domain\Auth\Controller\UserController;
use Domain\Login\Controller\LoginController;
use Domain\Register\Controller\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('/test-data', TestApiController::class);
Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', UserController::class);
    Route::post('/logout', LogoutController::class);
});
