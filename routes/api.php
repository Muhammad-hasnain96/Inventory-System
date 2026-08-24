<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Branch routes (Admin only)
    Route::prefix('/branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::get('/{branch}', [BranchController::class, 'show']);
        Route::post('/', [BranchController::class, 'store']);
        Route::put('/{branch}', [BranchController::class, 'update']);
        Route::delete('/{branch}', [BranchController::class, 'destroy']);
        Route::get('/{branch}/stats', [BranchController::class, 'stats']);
    });

    // Product routes (Admin only for create/update/delete)
    Route::prefix('/products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/low-stock', [ProductController::class, 'lowStockProducts']);
        Route::get('/{product}', [ProductController::class, 'show']);
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{product}', [ProductController::class, 'update']);
        Route::delete('/{product}', [ProductController::class, 'destroy']);
        Route::delete('/{product}/force', [ProductController::class, 'forceDelete']);
        Route::patch('/{product}/status', [ProductController::class, 'updateStatus']);
    });

    // Inventory routes (Admin or Branch Manager)
    Route::prefix('/inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/low-stock', [InventoryController::class, 'lowStock']);
        Route::post('/{inventory}/add-stock', [InventoryController::class, 'addStock']);
        Route::post('/{inventory}/adjust-stock', [InventoryController::class, 'adjustStock']);
        Route::post('/{inventory}/transfer', [InventoryController::class, 'transfer']);
        Route::get('/{inventory}/history', [InventoryController::class, 'movementHistory']);
    });

    // Order routes (All authenticated users)
    Route::prefix('/orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{order}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/{order}/cancel', [OrderController::class, 'cancel']);
    });

    // Dashboard routes (All authenticated users)
    Route::prefix('/dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/sales-report', [DashboardController::class, 'salesReport']);
        Route::get('/inventory-report', [DashboardController::class, 'inventoryReport']);
    });

    // Reports routes (Admin only)
    Route::prefix('/reports')->group(function () {
        Route::get('/export', [DashboardController::class, 'export']);
    });
});
