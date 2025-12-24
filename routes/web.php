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
    Route::resource('product-categories', \App\Http\Controllers\ProductCategoryController::class);
    Route::resource('packings', \App\Http\Controllers\PackingController::class);
    Route::resource('discount-schemes', \App\Http\Controllers\DiscountSchemeController::class);
    Route::resource('stocks', \App\Http\Controllers\StockController::class);
    Route::post('stocks/{stock}/adjust', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stocks.adjust');
    Route::resource('stock-ins', \App\Http\Controllers\StockInController::class);
    Route::post('stock-ins/{stockIn}/post', [\App\Http\Controllers\StockInController::class, 'post'])->name('stock-ins.post');
    Route::post('opening-stocks/convert-from-stocks', [\App\Http\Controllers\OpeningStockController::class, 'convertFromStocks'])->name('opening-stocks.convert');
    Route::resource('opening-stocks', \App\Http\Controllers\OpeningStockController::class);
    Route::post('opening-stocks/{openingStock}/post', [\App\Http\Controllers\OpeningStockController::class, 'post'])->name('opening-stocks.post');
    Route::post('closing-stocks/convert-from-stocks', [\App\Http\Controllers\ClosingStockController::class, 'convertFromStocks'])->name('closing-stocks.convert');
    Route::resource('closing-stocks', \App\Http\Controllers\ClosingStockController::class);
    Route::post('closing-stocks/{closingStock}/post', [\App\Http\Controllers\ClosingStockController::class, 'post'])->name('closing-stocks.post');
    Route::post('closing-stocks/{closingStock}/revert', [\App\Http\Controllers\ClosingStockController::class, 'revert'])->name('closing-stocks.revert');
    Route::resource('stock-reports', \App\Http\Controllers\StockReportController::class)->only(['index']);
    Route::resource('low-stock-reports', \App\Http\Controllers\LowStockReportController::class)->only(['index']);
    Route::resource('stock-outs', \App\Http\Controllers\StockOutController::class);
    Route::post('stock-outs/{stockOut}/post', [\App\Http\Controllers\StockOutController::class, 'post'])->name('stock-outs.post');
    Route::get('customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('customers', CustomerController::class);
    Route::resource('customer-attributes', \App\Http\Controllers\CustomerAttributeController::class);
    Route::resource('order-bookers', \App\Http\Controllers\OrderBookerController::class);
    Route::resource('order-booker-targets', \App\Http\Controllers\OrderBookerTargetController::class);
    Route::get('target-sheets', [\App\Http\Controllers\TargetSheetController::class, 'index'])->name('target-sheets.index');
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
    Route::resource('channels', \App\Http\Controllers\ChannelController::class);
    Route::resource('sub-addresses', \App\Http\Controllers\SubAddressController::class);
    Route::resource('sub-distributions', \App\Http\Controllers\SubDistributionController::class);
});

require __DIR__.'/auth.php';
