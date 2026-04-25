<?php

use Domain\Auth\Controller\LogoutController;
use Domain\Auth\Controller\UserController;
use Domain\Login\Controller\LoginController;
use Domain\Portfolio\Controller\PortfolioController;
use Domain\Register\Controller\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', UserController::class);
    Route::post('/logout', LogoutController::class);
    Route::apiResource('/portfolios', PortfolioController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);
});
