<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StockTransactionController;

// Public
Route::post('/login',    [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Ingredients / Inventory
    Route::apiResource('ingredients', IngredientController::class);
    Route::get('/inventory/low-stock',  [IngredientController::class, 'lowStock']);
    Route::post('/ingredients/{ingredient}/restock', [IngredientController::class, 'restock']);

    // Stock transactions
    Route::get('/stock-transactions',  [StockTransactionController::class, 'index']);

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Orders / POS
    Route::apiResource('orders', OrderController::class);
    Route::post('/orders/{order}/pay',    [OrderController::class, 'pay']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt']);
    Route::get('/reports/sales',          [OrderController::class, 'salesReport']);
});
