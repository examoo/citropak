<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\InvoiceController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']); // Maybe standard auth/user

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Sync Routes
        Route::prefix('sync')->group(function () {
            Route::get('/master', [SyncController::class, 'masterData']); 
            Route::post('/push', [SyncController::class, 'pushTransactions']);
        });

        // Other Resources (Customer, Visits, Invoices - kept for direct access if needed)
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::prefix('visits')->group(function () {
            Route::post('/check-in', [CustomerController::class, 'checkIn']);
            Route::post('/check-out', [CustomerController::class, 'checkOut']);
        });
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });
});
