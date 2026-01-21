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

// Maintenance route to clear cache and refresh autoloader
Route::get('/clear-cache', function () {
    $output = [];
    
    // Clear configuration cache
    \Artisan::call('config:clear');
    $output[] = 'Config cache cleared';
    
    // Clear application cache
    \Artisan::call('cache:clear');
    $output[] = 'Application cache cleared';
    
    // Clear view cache
    \Artisan::call('view:clear');
    $output[] = 'View cache cleared';
    
    // Clear route cache
    \Artisan::call('route:clear');
    $output[] = 'Route cache cleared';
    
    // Optimize (clears and rebuilds class autoloader)
    \Artisan::call('optimize:clear');
    $output[] = 'Optimize cleared';
    
    return response()->json([
        'status' => 'success',
        'message' => 'All caches cleared successfully',
        'details' => $output,
        'note' => 'For composer operations, use extract.php or SSH'
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Main application routes
Route::middleware(['auth'])->group(function () {
    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class);
    Route::get('stock-report/export', [\App\Http\Controllers\StockReportController::class, 'export'])->name('stock-report.export');
    Route::get('stock-report', [\App\Http\Controllers\StockReportController::class, 'index'])->name('stock-report.index');
    Route::get('low-stock-report/export', [\App\Http\Controllers\LowStockReportController::class, 'export'])->name('low-stock-report.export');
    Route::get('low-stock-report', [\App\Http\Controllers\LowStockReportController::class, 'index'])->name('low-stock-report.index');
    Route::resource('product-types', ProductTypeController::class);
    Route::resource('product-categories', \App\Http\Controllers\ProductCategoryController::class);
    Route::resource('packings', \App\Http\Controllers\PackingController::class);
    
    // Chillers
    Route::get('chillers/report/export', [\App\Http\Controllers\ChillerController::class, 'exportReport'])->name('chillers.report.export');
    Route::get('chillers/report', [\App\Http\Controllers\ChillerController::class, 'report'])->name('chillers.report');
    Route::resource('chillers', \App\Http\Controllers\ChillerController::class);
    Route::put('chillers/{chiller}/move', [\App\Http\Controllers\ChillerController::class, 'move'])->name('chillers.move');
    Route::put('chillers/{chiller}/return', [\App\Http\Controllers\ChillerController::class, 'returnChiller'])->name('chillers.return');
    Route::get('chillers/{chiller}/history', [\App\Http\Controllers\ChillerController::class, 'history'])->name('chillers.history');
    Route::resource('chiller-types', \App\Http\Controllers\ChillerTypeController::class);
    
    // Shelves
    Route::get('shelves/report/export', [\App\Http\Controllers\ShelfController::class, 'exportReport'])->name('shelves.report.export');
    Route::get('shelves/report', [\App\Http\Controllers\ShelfController::class, 'report'])->name('shelves.report');
    Route::resource('shelves', \App\Http\Controllers\ShelfController::class);
    Route::get('discount-schemes/template', [\App\Http\Controllers\DiscountSchemeController::class, 'downloadTemplate'])->name('discount-schemes.template');
    Route::post('discount-schemes/import', [\App\Http\Controllers\DiscountSchemeController::class, 'import'])->name('discount-schemes.import');
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
    Route::post('customers/bulk-destroy', [CustomerController::class, 'bulkDestroy'])->name('customers.bulk-destroy');
    Route::post('customers/delete-all', [CustomerController::class, 'deleteAll'])->name('customers.delete-all');
    Route::get('customers/{customer}/discounts', [CustomerController::class, 'discounts'])->name('customers.discounts');
    Route::post('customers/{customer}/discounts', [CustomerController::class, 'saveDiscounts'])->name('customers.save-discounts');
    Route::get('customer-discounts', [CustomerController::class, 'discountsIndex'])->name('customer-discounts.index');
    Route::resource('customers', CustomerController::class);
    Route::resource('customer-attributes', \App\Http\Controllers\CustomerAttributeController::class);
    Route::resource('order-bookers', \App\Http\Controllers\OrderBookerController::class);
    Route::resource('order-booker-targets', \App\Http\Controllers\OrderBookerTargetController::class);
    Route::get('target-sheets', [\App\Http\Controllers\TargetSheetController::class, 'index'])->name('target-sheets.index');
    Route::resource('vans', \App\Http\Controllers\VanController::class);
    Route::get('customer-sheets', [\App\Http\Controllers\CustomerSheetController::class, 'index'])->name('customer-sheets.index');
    Route::post('customer-sheets/assign-to-van', [\App\Http\Controllers\CustomerSheetController::class, 'assignToVan'])->name('customer-sheets.assign-to-van');
    
    // Good Issue Notes
    // Invoices
    Route::get('van-invoices', [\App\Http\Controllers\InvoiceController::class, 'vanInvoice'])->name('van-invoices.index');
    Route::post('invoices/{invoice}/mark-credit', [\App\Http\Controllers\InvoiceController::class, 'markAsCredit'])->name('invoices.mark-credit');
    Route::post('invoices/{invoice}/resync-fbr', [\App\Http\Controllers\InvoiceController::class, 'resyncFbr'])->name('invoices.resync-fbr');
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);
    Route::resource('recoveries', \App\Http\Controllers\RecoveryController::class);
    Route::get('sales-accounts', [\App\Http\Controllers\SalesAccountController::class, 'index'])->name('sales-accounts.index');
    Route::get('sales-sheets', [\App\Http\Controllers\SalesSheetController::class, 'index'])->name('sales-sheets.index');
    Route::get('sales-reports/export', [\App\Http\Controllers\SalesReportController::class, 'export'])->name('sales-reports.export');
    Route::get('sales-reports', [\App\Http\Controllers\SalesReportController::class, 'index'])->name('sales-reports.index');
    Route::get('customer-sales-reports/export', [\App\Http\Controllers\CustomerSalesReportController::class, 'export'])->name('customer-sales-reports.export');
    Route::get('customer-sales-reports', [\App\Http\Controllers\CustomerSalesReportController::class, 'index'])->name('customer-sales-reports.index');
    Route::get('customer-ledgers', [\App\Http\Controllers\CustomerLedgerController::class, 'index'])->name('customer-ledgers.index');
    Route::get('daily-sales-reports/export', [\App\Http\Controllers\DailySalesReportController::class, 'export'])->name('daily-sales-reports.export');
    Route::get('daily-sales-reports', [\App\Http\Controllers\DailySalesReportController::class, 'index'])->name('daily-sales-reports.index');
    Route::get('van-comparison', [\App\Http\Controllers\VanComparisonController::class, 'index'])->name('van-comparison.index');
    Route::get('sale-tax-invoices-reports/export', [\App\Http\Controllers\SaleTaxInvoicesReportController::class, 'export'])->name('sale-tax-invoices-reports.export');
    Route::get('sale-tax-invoices-reports', [\App\Http\Controllers\SaleTaxInvoicesReportController::class, 'index'])->name('sale-tax-invoices-reports.index');
    Route::get('day-closing', [\App\Http\Controllers\DayClosingController::class, 'index'])->name('day-closing.index');
    Route::get('customer-wise-discount-reports', [\App\Http\Controllers\CustomerWiseDiscountReportController::class, 'index'])->name('customer-wise-discount-reports.index');
    Route::get('brand-wise-sales-reports/export', [\App\Http\Controllers\BrandWiseSalesReportController::class, 'export'])->name('brand-wise-sales-reports.export');
    Route::get('brand-wise-sales-reports', [\App\Http\Controllers\BrandWiseSalesReportController::class, 'index'])->name('brand-wise-sales-reports.index');
    Route::get('api/order-bookers-by-van/{van}', [\App\Http\Controllers\InvoiceController::class, 'getOrderBookersByVan'])->name('api.bookers-by-van');
    Route::get('api/customers-by-booker/{booker}', [\App\Http\Controllers\InvoiceController::class, 'getCustomersByBooker'])->name('api.customers-by-booker');
    Route::get('api/customers-by-van/{vanCode}', [\App\Http\Controllers\InvoiceController::class, 'getCustomersByVan'])->name('api.customers-by-van');
    Route::get('api/customer-by-code/{code}', [\App\Http\Controllers\InvoiceController::class, 'getCustomerByCode'])->name('api.customer-by-code');
    Route::get('api/customer-brand-discount/{customer}/{product}', [\App\Http\Controllers\InvoiceController::class, 'getCustomerBrandDiscount'])->name('api.customer-brand-discount');
    Route::get('api/product-by-code/{code}', [\App\Http\Controllers\InvoiceController::class, 'getProductByCode'])->name('api.product-by-code');
    Route::get('api/schemes-for-product/{product}', [\App\Http\Controllers\InvoiceController::class, 'getSchemesForProduct'])->name('api.schemes-for-product');
    Route::get('api/discount-schemes/{product}', [\App\Http\Controllers\InvoiceController::class, 'getDiscountSchemes'])->name('api.discount-schemes');
    Route::get('good-issue-notes/pending-items', [\App\Http\Controllers\GoodIssueNoteController::class, 'getPendingItems'])->name('good-issue-notes.pending-items');
    Route::post('good-issue-notes/{good_issue_note}/issue', [\App\Http\Controllers\GoodIssueNoteController::class, 'issue'])->name('good-issue-notes.issue');
    Route::post('good-issue-notes/{good_issue_note}/cancel', [\App\Http\Controllers\GoodIssueNoteController::class, 'cancel'])->name('good-issue-notes.cancel');
    Route::resource('good-issue-notes', \App\Http\Controllers\GoodIssueNoteController::class);
    
    // Customer Wise Discount Report
    Route::get('customer-wise-discount-report/export', [\App\Http\Controllers\CustomerWiseDiscountReportController::class, 'export'])->name('customer-wise-discount-report.export');
    Route::get('customer-wise-discount-report', [\App\Http\Controllers\CustomerWiseDiscountReportController::class, 'index'])->name('customer-wise-discount-report.index');
    
    // Credit Management
    Route::get('credit-management', [\App\Http\Controllers\CreditManagementController::class, 'index'])->name('credit-management.index');
    Route::get('credit-management/entries', [\App\Http\Controllers\CreditManagementController::class, 'entries'])->name('credit-management.entries');
    Route::get('credit-management/summary/export', [\App\Http\Controllers\CreditManagementController::class, 'exportSummary'])->name('credit-management.summary.export');
    Route::get('credit-management/summary', [\App\Http\Controllers\CreditManagementController::class, 'summary'])->name('credit-management.summary');
    Route::get('credit-management/bill-summary/export', [\App\Http\Controllers\CreditManagementController::class, 'exportBillSummary'])->name('credit-management.bill-summary.export');
    Route::get('credit-management/bill-summary', [\App\Http\Controllers\CreditManagementController::class, 'billSummary'])->name('credit-management.bill-summary');
    Route::get('credit-management/bill-wise-recovery', [\App\Http\Controllers\CreditManagementController::class, 'billWiseRecovery'])->name('credit-management.bill-wise-recovery');
    Route::get('credit-management/daily-report/export', [\App\Http\Controllers\CreditManagementController::class, 'exportDailyReport'])->name('credit-management.daily-report.export');
    Route::get('credit-management/daily-report', [\App\Http\Controllers\CreditManagementController::class, 'dailyReport'])->name('credit-management.daily-report');
    Route::get('credit-management/daily-progress', [\App\Http\Controllers\CreditManagementController::class, 'dailyProgress'])->name('credit-management.daily-progress');
    Route::get('credit-management/search', [\App\Http\Controllers\CreditManagementController::class, 'search'])->name('credit-management.search');
    Route::get('credit-management/sales-sheet', [\App\Http\Controllers\CreditManagementController::class, 'salesSheet'])->name('credit-management.sales-sheet');
    Route::resource('credit-bookers', \App\Http\Controllers\CreditBookerController::class)->except(['create', 'edit', 'show']);
    
    // Distribution-Based Modules
    Route::post('distributions/{distribution}/switch', [\App\Http\Controllers\DistributionController::class, 'switch'])->name('distributions.switch');
    Route::resource('distributions', \App\Http\Controllers\DistributionController::class);
    Route::resource('brands', \App\Http\Controllers\BrandController::class);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class);
    Route::resource('routes', \App\Http\Controllers\RouteController::class);
    Route::resource('schemes', \App\Http\Controllers\SchemeController::class);
    Route::resource('holidays', \App\Http\Controllers\HolidayController::class);
    
    // FBR Settings
    Route::prefix('settings/fbr')->group(function () {
        Route::get('/', [\App\Http\Controllers\FbrSettingsController::class, 'show'])->name('fbr-settings.show');
        Route::put('/', [\App\Http\Controllers\FbrSettingsController::class, 'update'])->name('fbr-settings.update');
        Route::post('/test', [\App\Http\Controllers\FbrSettingsController::class, 'testConnection'])->name('fbr-settings.test');
        Route::get('/summary', [\App\Http\Controllers\FbrSettingsController::class, 'summary'])->name('fbr-settings.summary');
    });
    
    // Notice Board
    Route::get('notice-board', [\App\Http\Controllers\NoticeBoardController::class, 'index'])->name('notice-board.index');
    Route::post('notice-board', [\App\Http\Controllers\NoticeBoardController::class, 'store'])->name('notice-board.store');
    Route::put('notice-board/{notice}', [\App\Http\Controllers\NoticeBoardController::class, 'update'])->name('notice-board.update');
    Route::delete('notice-board/{notice}', [\App\Http\Controllers\NoticeBoardController::class, 'destroy'])->name('notice-board.destroy');
    
    // Reorder Live
    Route::get('reorder-live', [\App\Http\Controllers\ReorderLiveController::class, 'index'])->name('reorder-live.index');
    Route::resource('channels', \App\Http\Controllers\ChannelController::class);
    Route::resource('sub-addresses', \App\Http\Controllers\SubAddressController::class);
    Route::resource('sub-distributions', \App\Http\Controllers\SubDistributionController::class);
});

require __DIR__.'/auth.php';
