<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Main application routes
Route::middleware(['auth'])->group(function () {
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('products', ProductController::class);
    Route::resource('product-types', ProductTypeController::class);
    Route::get('customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('customers', CustomerController::class);
    Route::resource('customer-attributes', \App\Http\Controllers\CustomerAttributeController::class);
    Route::resource('order-bookers', \App\Http\Controllers\OrderBookerController::class);
    Route::resource('vans', \App\Http\Controllers\VanController::class);
    Route::get('customer-sheets', [\App\Http\Controllers\CustomerSheetController::class, 'index'])->name('customer-sheets.index');
    
    // Distribution-Based Modules
    Route::post('distributions/{distribution}/switch', [\App\Http\Controllers\DistributionController::class, 'switch'])->name('distributions.switch');
    Route::resource('distributions', \App\Http\Controllers\DistributionController::class);
    Route::resource('brands', \App\Http\Controllers\BrandController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('routes', \App\Http\Controllers\RouteController::class);
    Route::resource('schemes', \App\Http\Controllers\SchemeController::class);
    Route::resource('holidays', \App\Http\Controllers\HolidayController::class);
});

require __DIR__.'/auth.php';
