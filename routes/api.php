<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\Auth\RegisteredUserController;

// Public registration route
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Customer routes
    Route::post('customers/quick-create', [V1\CustomerController::class, 'quickCreate']);
    Route::get('customers/metrics', [V1\CustomerController::class, 'getMetrics']);
    Route::get('customers/{customer}/historical-purchases', [V1\CustomerController::class, 'getHistoricalPurchases']);
    Route::get('customers/{id}/product-history', [V1\CustomerController::class, 'getProductHistory']);

    // Product routes
    Route::post('products/quick-create', [V1\ProductController::class, 'quickCreate']);
    Route::post('products/bulk-delete', [V1\ProductController::class, 'bulkDelete']);
    Route::put('products/bulk-update-stock', [V1\ProductController::class, 'bulkUpdateStock']);
    Route::post('products/adjust-stock', [V1\ProductController::class, 'adjustStock']);
    Route::get('products/search', [V1\ProductController::class, 'search']);
    Route::get('products/expiring-soon', [V1\ProductController::class, 'getExpiringSoonProducts']);
    Route::get('products/metrics', [V1\ProductController::class, 'getProductMetrics']);
    Route::get('products/{id}/adjustment-log', [V1\ProductController::class, 'getAdjustmentLog']);
    Route::get('products/search-by-barcode', [V1\ProductController::class, 'searchByBarcode']);

    // Purchase routes
    Route::post('purchases/bulk-delete', [V1\PurchaseController::class, 'bulkDelete']);
    Route::put('purchases/bulk-mark-paid', [V1\PurchaseController::class, 'bulkMarkPaid']);
    Route::get('purchases/metrics', [V1\PurchaseController::class, 'getPurchaseMetrics']);
    Route::get('purchases/expiring-soon', [V1\PurchaseController::class, 'getExpiringSoonPurchases']);
    Route::post('purchases/{id}/payment', [V1\PurchaseController::class, 'addPayment']);

    // Sales routes
    Route::post('sales/bulk-delete', [V1\SalesController::class, 'bulkDelete']);
    Route::put('sales/bulk-mark-paid', [V1\SalesController::class, 'bulkMarkPaid']);
    Route::get('sales/metrics', [V1\SalesController::class, 'getSalesMetrics']);
    Route::get('sales/expiring-soon', [V1\SalesController::class, 'getExpiringSoonSales']);
    Route::post('sales/{id}/payment', [V1\SalesController::class, 'addPayment']);
    Route::get('sales/customer-price/{customer}/{product}', [V1\SalesController::class, 'getCustomerPrice']);

    // Supplier routes
    Route::get('suppliers/metrics', [V1\SupplierController::class, 'getMetrics']);
    Route::get('suppliers/{id}/historical-purchases', [V1\SupplierController::class, 'getHistoricalPurchases']);
    Route::get('suppliers/{id}/product-history', [V1\SupplierController::class, 'getProductHistory']);

    // Sales Pipeline routes
    Route::post('sales-pipelines/{pipeline}/stages', [V1\SalesPipelineController::class, 'storeStage']);
    Route::post('sales-pipelines/{pipeline}/stages/reorder', [V1\SalesPipelineController::class, 'reorderStages']);
    Route::put('sales-opportunities/{opportunity}/move', [V1\SalesOpportunityController::class, 'moveOpportunity']);
    Route::post('sales-opportunities/{opportunity}/convert', [V1\SalesOpportunityController::class, 'convertToSalesOrder']);

    // Warehouse routes
    Route::post('warehouses/{id}/set-main', [V1\WarehouseController::class, 'setMain']);
    Route::post('warehouses/{id}/unset-main', [V1\WarehouseController::class, 'unsetMain']);

    // Report routes
    Route::get('reports/adjustment-log', [V1\ReportController::class, 'adjustmentLog']);
    Route::get('reports/recent-transactions', [V1\ReportController::class, 'recentTransactions']);
    Route::post('reports/transactions/bulk-mark-paid', [V1\ReportController::class, 'bulkMarkAsPaid']);
    Route::post('reports/transactions/{id}/mark-paid', [V1\ReportController::class, 'markAsPaid']);

    // Notification routes
    Route::get('notifications/count', [V1\NotificationController::class, 'count']);
    Route::get('notifications', [V1\NotificationController::class, 'getNotifications']);
    Route::post('notifications/{id}/mark-as-read', [V1\NotificationController::class, 'markAsRead']);

    // Settings routes
    Route::get('settings', [V1\SettingsController::class, 'getSettings']);
    Route::put('settings/theme-mode', [V1\SettingsController::class, 'updateThemeMode']);

    // User routes
    Route::get('users/roles-permissions', [V1\UserController::class, 'getRolePermissions']);

    // Tax routes
    Route::get('tax/active-rate', [V1\TaxController::class, 'getActiveTax']);

    Route::apiResource('accounts', V1\AccountController::class);
    Route::apiResource('categories', V1\CategoryController::class);
    Route::apiResource('currency-settings', V1\CurrencySettingController::class);
    Route::apiResource('customers', V1\CustomerController::class);
    Route::apiResource('customer-interactions', V1\CustomerInteractionController::class);
    Route::apiResource('journal-entries', V1\JournalEntryController::class);
    Route::apiResource('payments', V1\PaymentController::class);
    Route::apiResource('pipeline-stages', V1\PipelineStageController::class)->only(['update', 'destroy']);
    Route::apiResource('po-items', V1\POItemController::class);

    Route::apiResource('products', V1\ProductController::class);
    Route::apiResource('purchases', V1\PurchaseController::class);
    Route::apiResource('roles', V1\RoleController::class);
    Route::apiResource('sales', V1\SalesController::class);
    Route::apiResource('sales-items', V1\SalesItemController::class);
    Route::apiResource('sales-opportunities', V1\SalesOpportunityController::class);
    Route::apiResource('sales-opportunity-items', V1\SalesOpportunityItemController::class);
    Route::apiResource('sales-pipelines', V1\SalesPipelineController::class);
    Route::apiResource('stock-adjustments', V1\StockAdjustmentController::class);
    Route::apiResource('suppliers', V1\SupplierController::class);
    Route::apiResource('supplier-interactions', V1\SupplierInteractionController::class);
    Route::apiResource('taxes', V1\TaxController::class);
    Route::apiResource('transactions', V1\TransactionController::class);
    Route::apiResource('units', V1\UnitController::class);
    Route::apiResource('users', V1\UserController::class);
    Route::apiResource('warehouses', V1\WarehouseController::class);
});
