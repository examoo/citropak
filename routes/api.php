<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\InvoiceController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::prefix('visits')->group(function () {
        Route::post('/check-in', [CustomerController::class, 'checkIn']);
        Route::post('/check-out', [CustomerController::class, 'checkOut']);
    });

    Route::prefix('sync')->group(function () {
        Route::get('/products', [SyncController::class, 'products']);
        Route::get('/schemas', [SyncController::class, 'schemas']);
    });

    Route::post('/invoices', [InvoiceController::class, 'store']);
});
