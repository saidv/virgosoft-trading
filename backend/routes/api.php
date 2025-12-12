<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TradeController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Trading Platform API Routes
|
*/

// Broadcast routes
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Public routes

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);

    // Orders
    // Get orderbook
    Route::get('/orders', [OrderController::class, 'index']);
    // Get user's orders
    Route::get('/orders/my', [OrderController::class, 'myOrders']);
    // Place order
    Route::post('/orders', [OrderController::class, 'store']);
    // Cancel order
    Route::post(
        '/orders/{id}/cancel',
        [OrderController::class, 'cancel']
    );

    // Trades (bonus feature)
    Route::get('/trades', [TradeController::class, 'index']);
});
