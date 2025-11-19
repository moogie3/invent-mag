<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('accounts', V1\AccountController::class);
    Route::apiResource('categories', V1\CategoryController::class);
    Route::apiResource('currency-settings', V1\CurrencySettingController::class);
    Route::apiResource('customers', V1\CustomerController::class);
    Route::apiResource('customer-interactions', V1\CustomerInteractionController::class);
    Route::apiResource('journal-entries', V1\JournalEntryController::class);
    Route::apiResource('payments', V1\PaymentController::class);
    Route::apiResource('pipeline-stages', V1\PipelineStageController::class);
    Route::apiResource('po-items', V1\POItemController::class);
    Route::apiResource('pos', V1\POSController::class);
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
