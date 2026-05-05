<?php

use Domain\Auth\Controller\LogoutController;
use Domain\Auth\Controller\UserController;
use Domain\Login\Controller\LoginController;
use Domain\Portfolio\Controller\PortfolioController;
use Domain\Portfolio\Controller\PortfolioPositionsController;
use Domain\Portfolio\Controller\PortfolioTransactionImportController;
use Domain\Register\Controller\RegisterController;
use Domain\Transaction\Controller\TransactionImportTypeController;
use Illuminate\Support\Facades\Route;

Route::post('/register', RegisterController::class);
Route::post('/login', LoginController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', UserController::class);
    Route::post('/logout', LogoutController::class);
    Route::get('/portfolios/{portfolio}/transactions/import-types', TransactionImportTypeController::class);
    Route::post('/portfolios/{portfolio}/transactions/import', PortfolioTransactionImportController::class);
    Route::get('/portfolios/{portfolio}/positions', PortfolioPositionsController::class);
    Route::apiResource('/portfolios', PortfolioController::class)->only([
        'show',
        'index',
        'store',
        'update',
        'destroy',
    ]);
});
