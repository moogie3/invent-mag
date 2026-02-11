<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\{CategoryController, CustomerController, ProductController, PurchaseController, SalesPipelineController, SupplierController, UnitController, CurrencyController, SalesController, DashboardController, ProfileController, NotificationController, POSController, ReportController, WarehouseController, TaxController, UserController, CustomerCrmController, SupplierCrmController, SettingsController, PurchaseReturnController, SalesReturnController, JournalEntryController};
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\Auth\LoginController;

// Admin Authentication Routes
Route::middleware('web')->prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        // Login
        Route::get('/login', function (Request $request) {
            $email = $request->session()->get('attempted_email');

            if ($email) {
                $throttleKey = Str::lower($email) . '|' . $request->ip();

                if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    return response()->view('errors.429', ['seconds' => $seconds], 429);
                }
            }

            return view('admin.auth.login');
        })->name('admin.login');

        // Forgot Password
        Route::get('/forgot-password', fn() => view('admin.auth.forgot-password'))->name('admin.password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('admin.password.email');

        // Reset Password
        Route::get('/reset-password/{token}', fn($token) => view('admin.auth.reset-password', ['token' => $token]))->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
    });

    // Email Verification Success Page
    Route::get('/email/verified', function () {
        return view('admin.auth.verify-email-success');
    })->name('verification.verified');

    // Override Fortify's verification.send route to ensure correct middleware
    Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['auth', 'throttle:6,1'])->name('verification.send');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['auth', 'verified'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // MOVED: Role-permissions route - accessible to authenticated users
        Route::get('/roles-permissions', [UserController::class, 'getRolePermissions'])->name('admin.roles-permissions');

        // Product Routes
        Route::prefix('product')->group(function () {
            Route::get('/', [ProductController::class, 'index'])
                ->name('admin.product')
                ->middleware('can:view-products');

            Route::get('/create', [ProductController::class, 'create'])
                ->name('admin.product.create')
                ->middleware('can:create-products');

            Route::post('/store', [ProductController::class, 'store'])
                ->name('admin.product.store')
                ->middleware('can:create-products');

            Route::get('/edit/{id}', [ProductController::class, 'edit'])
                ->name('admin.product.edit')
                ->middleware('can:edit-products');

            Route::put('/update/{id}', [ProductController::class, 'update'])
                ->name('admin.product.update')
                ->middleware('can:edit-products');

            Route::delete('/destroy/{id}', [ProductController::class, 'destroy'])
                ->name('admin.product.destroy')
                ->middleware('can:delete-products');

            Route::get('/view/{id}', [ProductController::class, 'view'])
                ->name('admin.product.view')
                ->middleware('can:view-products');

            Route::get('/modal-view/{id}', [ProductController::class, 'modalView'])
                ->name('admin.product.modal-view')
                ->middleware('can:view-products');

            Route::get('/{id}/adjustment-log', [ProductController::class, 'getAdjustmentLog'])
                ->name('admin.product.adjustment-log')
                ->middleware('can:view-products');

            Route::post('/quick-create', [ProductController::class, 'quickCreate'])
                ->name('admin.product.quickCreate')
                ->middleware('can:create-products');

            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])
                ->name('product.bulk-delete')
                ->middleware('can:delete-products');

            Route::post('/bulk-export', [ProductController::class, 'bulkExport'])
                ->name('product.bulk-export')
                ->middleware('can:view-products');

            Route::post('/bulk-stock-details', [ProductController::class, 'bulkStockDetails'])
                ->name('product.bulk-stock-details')
                ->middleware('can:view-products');

            Route::post('/bulk-update-stock', [ProductController::class, 'bulkUpdateStock'])
                ->name('product.bulk-update-stock')
                ->middleware('can:edit-products');

            Route::post('/adjust-stock', [ProductController::class, 'adjustStock'])
                ->name('admin.product.adjust-stock')
                ->middleware('can:edit-products');

            Route::get('/search', [ProductController::class, 'search'])
                ->name('admin.product.search')
                ->middleware('can:view-products');

            Route::get('/search-by-barcode', [ProductController::class, 'searchByBarcode'])
                ->name('admin.product.search-by-barcode')
                ->middleware('can:view-products');

            Route::get('/metrics', [ProductController::class, 'getProductMetrics'])
                ->name('admin.product.metrics')
                ->middleware('can:view-products');

            Route::get('/expiring-soon', [ProductController::class, 'getExpiringSoonProducts'])
                ->name('admin.product.expiring-soon')
                ->middleware('can:view-products');
        });

        // Supplier Routes
        Route::prefix('supplier')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('admin.supplier')->middleware('can:view-supplier');
            Route::get('/create', [SupplierController::class, 'create'])->name('admin.supplier.create')->middleware('can:create-supplier');
            Route::post('/store', [SupplierController::class, 'store'])->name('admin.supplier.store')->middleware('can:create-supplier');
            Route::get('/edit/{id}', [SupplierController::class, 'edit'])->name('admin.supplier.edit')->middleware('can:edit-supplier');
            Route::get('/view/{id}', [SupplierController::class, 'view'])->name('admin.supplier.view')->middleware('can:view-supplier');
            Route::put('/update/{id}', [SupplierController::class, 'update'])->name('admin.supplier.update')->middleware('can:edit-supplier');
            Route::delete('/destroy/{id}', [SupplierController::class, 'destroy'])->name('admin.supplier.destroy')->middleware('can:delete-supplier');
            Route::get('/metrics', [SupplierController::class, 'getMetrics'])->name('admin.supplier.metrics')->middleware('can:view-supplier');
            Route::post('/export', [SupplierController::class, 'exportAll'])
                ->name('admin.supplier.export')
                ->middleware('can:view-supplier');
        });

        // Customer Routes
        Route::prefix('customer')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('admin.customer')->middleware('can:view-customer');
            Route::get('/create', [CustomerController::class, 'create'])->name('admin.customer.create')->middleware('can:create-customer');
            Route::post('/store', [CustomerController::class, 'store'])->name('admin.customer.store')->middleware('can:create-customer');
            Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('admin.customer.edit')->middleware('can:edit-customer');
            Route::get('/view/{id}', [CustomerController::class, 'view'])->name('admin.customer.view')->middleware('can:view-customer');
            Route::put('/update/{id}', [CustomerController::class, 'update'])->name('admin.customer.update')->middleware('can:edit-customer');
            Route::delete('/destroy/{id}', [CustomerController::class, 'destroy'])->name('admin.customer.destroy')->middleware('can:delete-customer');
            Route::post('/quick-create', [CustomerController::class, 'quickCreate'])->name('admin.customer.quickCreate')->middleware('can:create-customer');
            Route::get('/metrics', [CustomerController::class, 'getMetrics'])->name('admin.customer.metrics')->middleware('can:view-customer');
            Route::get('/{customer}/historical-purchases', [CustomerCrmController::class, 'getHistoricalPurchases'])->name('admin.customer.historical-purchases')->middleware('can:view-customer');
            Route::post('/export', [CustomerController::class, 'exportAll'])
                ->name('admin.customer.export')
                ->middleware('can:view-customer');
        });

        // CRM Routes - Protected by customer permissions
        Route::middleware('can:view-customer')->group(function () {
            Route::get('/customers/{id}/crm-details', [CustomerCrmController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/customers/{id}/historical-purchases', [CustomerCrmController::class, 'getHistoricalPurchases'])->where('id', '[0-9]+');
            Route::get('/customers/{id}/product-history', [CustomerCrmController::class, 'getProductHistory'])->where('id', '[0-9]+');
        });

        Route::middleware('can:create-customer-interactions')->group(function () {
            Route::post('/customers/{id}/interactions', [CustomerCrmController::class, 'storeInteraction'])->where('id', '[0-9]+');
        });

        // SRM Routes - Protected by supplier permissions
        Route::middleware('can:view-supplier')->group(function () {
            Route::get('/suppliers/{id}/srm-details', [SupplierCrmController::class, 'show'])->where('id', '[0-9]+');
            Route::get('/suppliers/{id}/historical-purchases', [SupplierCrmController::class, 'getHistoricalPurchases'])->where('id', '[0-9]+');
            Route::get('/suppliers/{id}/product-history', [SupplierCrmController::class, 'getProductHistory'])->where('id', '[0-9]+');
        });

        Route::middleware('can:edit-supplier')->group(function () {
            Route::post('/suppliers/{id}/interactions', [SupplierCrmController::class, 'storeInteraction'])->where('id', '[0-9]+');
        });

        // Sales Pipeline Routes - Protected by sales pipeline permissions
        Route::middleware('can:view-sales-pipeline')->group(function () {
            Route::get('/sales-pipeline', [SalesPipelineController::class, 'index'])->name('admin.sales_pipeline.index');

            Route::prefix('sales-pipeline')->group(function () {
                // View routes
                Route::get('/pipelines', [SalesPipelineController::class, 'indexPipelines'])->name('admin.sales_pipeline.pipelines.index');
                Route::get('/opportunities', [SalesPipelineController::class, 'indexOpportunities'])->name('admin.sales_pipeline.opportunities.index');
                Route::get('/opportunities/{opportunity}', [SalesPipelineController::class, 'showOpportunity'])->name('admin.sales_pipeline.opportunities.show');
                Route::get('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'showConvertForm'])->name('admin.sales_pipeline.opportunities.convert.show');
                Route::post('/export', [SalesPipelineController::class, 'exportAll'])->name('admin.sales-pipeline.export');

                // Pipeline management routes
                Route::middleware('can:manage-sales-pipelines')->group(function () {
                    Route::post('/pipelines', [SalesPipelineController::class, 'storePipeline'])->name('admin.sales_pipeline.pipelines.store');
                    Route::put('/pipelines/{pipeline}', [SalesPipelineController::class, 'updatePipeline'])->name('admin.sales_pipeline.pipelines.update');
                    Route::delete('/pipelines/{pipeline}', [SalesPipelineController::class, 'destroyPipeline'])->name('admin.sales_pipeline.pipelines.destroy');
                });

                // Stage management routes
                Route::middleware('can:manage-pipeline-stages')->group(function () {
                    Route::post('/pipelines/{pipeline}/stages', [SalesPipelineController::class, 'storeStage'])->name('admin.sales_pipeline.stages.store');
                    Route::put('/stages/{stage}', [SalesPipelineController::class, 'updateStage'])->name('admin.sales_pipeline.stages.update');
                    Route::delete('/stages/{stage}', [SalesPipelineController::class, 'destroyStage'])->name('admin.sales_pipeline.stages.destroy');
                    Route::post('/pipelines/{pipeline}/stages/reorder', [SalesPipelineController::class, 'reorderStages'])->name('admin.sales_pipeline.stages.reorder');
                });

                // Opportunity management routes
                Route::middleware('can:manage-sales-opportunities')->group(function () {
                    Route::post('/opportunities', [SalesPipelineController::class, 'storeOpportunity'])->name('admin.sales_pipeline.opportunities.store');
                    Route::post('/opportunities/{opportunity}', [SalesPipelineController::class, 'updateOpportunity'])->name('admin.sales_pipeline.opportunities.update');
                    Route::delete('/opportunities/{opportunity}', [SalesPipelineController::class, 'destroyOpportunity'])->name('admin.sales_pipeline.opportunities.destroy');
                    Route::put('/opportunities/{opportunity}/move', [SalesPipelineController::class, 'moveOpportunity'])->name('admin.sales_pipeline.opportunities.move');
                    Route::post('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'convertToSalesOrder'])->name('admin.sales_pipeline.opportunities.convert');
                });
            });
        });

        // Purchase Order Routes
        Route::prefix('po')->group(function () {
            Route::get('/', [PurchaseController::class, 'index'])
                ->name('admin.po')
                ->middleware('can:view-purchase-orders');

            Route::get('/create', [PurchaseController::class, 'create'])
                ->name('admin.po.create')
                ->middleware('can:create-purchase-orders');

            Route::post('/store', [PurchaseController::class, 'store'])
                ->name('admin.po.store')
                ->middleware('can:create-purchase-orders');

            Route::get('/edit/{id}', [PurchaseController::class, 'edit'])
                ->name('admin.po.edit')
                ->middleware('can:edit-purchase-orders');

            Route::get('/view/{id}', [PurchaseController::class, 'view'])
                ->name('admin.po.view')
                ->middleware('can:view-purchase-orders');

            Route::put('/update/{id}', [PurchaseController::class, 'update'])
                ->name('admin.po.update')
                ->middleware('can:edit-purchase-orders');

            Route::delete('/destroy/{id}', [PurchaseController::class, 'destroy'])
                ->name('admin.po.destroy')
                ->middleware('can:delete-purchase-orders');

            Route::get('/product/{id}', [PurchaseController::class, 'getProductDetails'])
                ->name('admin.po.product.details')
                ->middleware('can:view-purchase-orders');

            Route::get('/modal-view/{id}', [PurchaseController::class, 'modalView'])
                ->name('admin.po.modal-view')
                ->middleware('can:view-purchase-orders');

            Route::post('/bulk-delete', [PurchaseController::class, 'bulkDelete'])
                ->name('po.bulk-delete')
                ->middleware('can:delete-purchase-orders');

            Route::post('/bulk-mark-paid', [PurchaseController::class, 'bulkMarkPaid'])
                ->name('po.bulk-mark-paid')
                ->middleware('can:edit-purchase-orders');

            Route::post('/bulk-export', [PurchaseController::class, 'bulkExport'])
                ->name('po.bulk-export')
                ->middleware('can:view-purchase-orders');

            Route::get('/metrics', [PurchaseController::class, 'getPurchaseMetrics'])
                ->name('admin.po.metrics')
                ->middleware('can:view-purchase-orders');

            Route::get('/expiring-soon', [PurchaseController::class, 'getExpiringSoonPurchases'])
                ->name('admin.po.expiring-soon')
                ->middleware('can:view-purchase-orders');

            Route::get('/print/{id}', [PurchaseController::class, 'print'])
                ->name('admin.po.print')
                ->middleware('can:view-purchase-orders');

            Route::post('/{id}/payment', [PurchaseController::class, 'addPayment'])
                ->name('admin.po.add-payment')
                ->middleware('can:edit-purchase-orders');
        });

        // Sales Routes
        Route::prefix('sales')->group(function () {
            Route::get('/', [SalesController::class, 'index'])
                ->name('admin.sales')
                ->middleware('can:view-sales');

            Route::get('/create', [SalesController::class, 'create'])
                ->name('admin.sales.create')
                ->middleware('can:create-sales');

            Route::post('/store', [SalesController::class, 'store'])
                ->name('admin.sales.store')
                ->middleware('can:create-sales');

            Route::get('/edit/{id}', [SalesController::class, 'edit'])
                ->name('admin.sales.edit')
                ->middleware('can:edit-sales');

            Route::get('/view/{id}', [SalesController::class, 'view'])
                ->name('admin.sales.view')
                ->middleware('can:view-sales');

            Route::put('/update/{id}', [SalesController::class, 'update'])
                ->name('admin.sales.update')
                ->middleware('can:edit-sales');

            Route::delete('/destroy/{id}', [SalesController::class, 'destroy'])
                ->name('admin.sales.destroy')
                ->middleware('can:delete-sales');

            Route::get('/product/{id}', [SalesController::class, 'getInvoiceDetails'])
                ->name('admin.sales.product.details')
                ->middleware('can:view-sales');

            Route::get('/modal-view/{id}', [SalesController::class, 'modalViews'])
                ->name('admin.sales.modal-view')
                ->middleware('can:view-sales');

            Route::post('/bulk-delete', [SalesController::class, 'bulkDelete'])
                ->name('sales.bulk-delete')
                ->middleware('can:delete-sales');

            Route::post('/bulk-mark-paid', [SalesController::class, 'bulkMarkPaid'])
                ->name('sales.bulk-mark-paid')
                ->middleware('can:edit-sales');

            Route::post('/bulk-export', [SalesController::class, 'bulkExport'])
                ->name('sales.bulk-export')
                ->middleware('can:view-sales');

            Route::get('/get-customer-price/{customer}/{product}', [SalesController::class, 'getCustomerPrice'])
                ->name('admin.sales.get-customer-price')
                ->middleware('can:view-sales');

            Route::get('/metrics', [SalesController::class, 'getSalesMetrics'])
                ->name('admin.sales.metrics')
                ->middleware('can:view-sales');

            Route::get('/expiring-soon', [SalesController::class, 'getExpiringSoonSales'])
                ->name('admin.sales.expiring-soon')
                ->middleware('can:view-sales');

            Route::get('/print/{id}', [SalesController::class, 'print'])
                ->name('admin.sales.print')
                ->middleware('can:view-sales');

            Route::post('/{id}/payment', [SalesController::class, 'addPayment'])
                ->name('admin.sales.add-payment')
                ->middleware('can:edit-sales');
        });

        // Sales Return Routes
        Route::get('sales-returns/sale/{sale}', [SalesReturnController::class, 'getSalesItems'])->name('admin.sales-returns.items');
        Route::get('sales-returns/{salesReturn}/modal-view', [SalesReturnController::class, 'modalView'])->name('admin.sales-returns.modal-view');
        Route::resource('sales-returns', SalesReturnController::class)->names('admin.sales-returns');
        Route::post('sales-returns/bulk-delete', [SalesReturnController::class, 'bulkDelete'])->name('admin.sales-returns.bulk-delete');
        Route::post('sales-returns/bulk-complete', [SalesReturnController::class, 'bulkComplete'])->name('admin.sales-returns.bulk-complete');
        Route::post('sales-returns/bulk-cancel', [SalesReturnController::class, 'bulkCancel'])->name('admin.sales-returns.bulk-cancel');
        Route::post('sales-returns/bulk-export', [SalesReturnController::class, 'bulkExport'])->name('admin.sales-returns.bulk-export');
        Route::get('sales-returns/print/{id}', [SalesReturnController::class, 'print'])->name('admin.sales-returns.print');

        // Purchase Return Routes
        Route::resource('por', PurchaseReturnController::class)->names('admin.por');
        Route::get('por/{por}/modal-view', [PurchaseReturnController::class, 'modalView'])->name('admin.por.modal-view');
        Route::get('por/purchase/{purchase}', [PurchaseReturnController::class, 'getPurchaseItems'])->name('admin.por.items');
        Route::post('por/bulk-delete', [PurchaseReturnController::class, 'bulkDelete'])->name('admin.por.bulk-delete');
        Route::post('por/bulk-complete', [PurchaseReturnController::class, 'bulkComplete'])->name('admin.por.bulk-complete');
        Route::post('por/bulk-cancel', [PurchaseReturnController::class, 'bulkCancel'])->name('admin.por.bulk-cancel');
        Route::post('por/bulk-export', [PurchaseReturnController::class, 'bulkExport'])->name('admin.por.bulk-export');
        Route::get('por/print/{id}', [PurchaseReturnController::class, 'print'])->name('admin.por.print');

        // Warehouse Routes
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index'])
                ->name('admin.warehouse')
                ->middleware('can:view-warehouses');

            Route::get('/create', [WarehouseController::class, 'create'])
                ->name('admin.warehouse.create')
                ->middleware('can:create-warehouses');

            Route::post('/store', [WarehouseController::class, 'store'])
                ->name('admin.warehouse.store')
                ->middleware('can:create-warehouses');

            Route::get('/edit/{id}', [WarehouseController::class, 'edit'])
                ->name('admin.warehouse.edit')
                ->middleware('can:edit-warehouses');

            Route::get('/view/{id}', [WarehouseController::class, 'view'])
                ->name('admin.warehouse.view')
                ->middleware('can:view-warehouses');

            Route::put('/update/{id}', [WarehouseController::class, 'update'])
                ->name('admin.warehouse.update')
                ->middleware('can:edit-warehouses');

            Route::delete('/destroy/{id}', [WarehouseController::class, 'destroy'])
                ->name('admin.warehouse.destroy')
                ->middleware('can:delete-warehouses');

            Route::get('/{id}/set-main', [WarehouseController::class, 'setMain'])
                ->name('admin.warehouse.set-main')
                ->middleware('can:edit-warehouses');

            Route::get('/{id}/unset-main', [WarehouseController::class, 'unsetMain'])
                ->name('admin.warehouse.unset-main')
                ->middleware('can:edit-warehouses');

            Route::post('/export', [WarehouseController::class, 'exportAll'])
                ->name('admin.warehouse.export')
                ->middleware('can:view-warehouses');
        });

        // POS Routes
        Route::prefix('pos')->group(function () {
            Route::get('/', [POSController::class, 'index'])
                ->name('admin.pos')
                ->middleware('can:access-pos');

            Route::get('/create', [POSController::class, 'create'])
                ->name('admin.pos.create')
                ->middleware('can:access-pos');

            Route::post('/store', [POSController::class, 'store'])
                ->name('admin.pos.store')
                ->middleware('can:access-pos');

            Route::get('/edit/{id}', [POSController::class, 'edit'])
                ->name('admin.pos.edit')
                ->middleware('can:access-pos');

            Route::get('/view/{id}', [POSController::class, 'view'])
                ->name('admin.pos.view')
                ->middleware('can:access-pos');

            Route::put('/update/{id}', [POSController::class, 'update'])
                ->name('admin.pos.update')
                ->middleware('can:access-pos');

            Route::delete('/destroy/{id}', [POSController::class, 'destroy'])
                ->name('admin.pos.destroy')
                ->middleware('can:delete-pos-transactions');

            Route::get('/receipt/{id}', [POSController::class, 'receipt'])
                ->name('admin.pos.receipt')
                ->middleware('can:access-pos');

            Route::get('/print-receipt/{id}', [POSController::class, 'printReceipt'])
                ->name('admin.pos.print-receipt')
                ->middleware('can:access-pos');
        });

        // Reports Routes
        Route::prefix('reports')->group(function () {
            Route::get('/income-statement', [ReportController::class, 'incomeStatement'])
                ->name('admin.reports.income-statement')
                ->middleware('can:view-financial-reports');

            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])
                ->name('admin.reports.balance-sheet')
                ->middleware('can:view-financial-reports');

            Route::get('/aged-receivables', [ReportController::class, 'agedReceivables'])
                ->name('admin.reports.aged-receivables')
                ->middleware('can:view-financial-reports');

            Route::get('/aged-payables', [ReportController::class, 'agedPayables'])
                ->name('admin.reports.aged-payables')
                ->middleware('can:view-financial-reports');

            Route::get('/adjustment-log', [ReportController::class, 'adjustmentLog'])
                ->name('admin.reports.adjustment-log')
                ->middleware('can:view-reports');

            Route::get('/recent-transactions', [ReportController::class, 'recentTransactions'])
                ->name('admin.reports.recent-transactions')
                ->middleware('can:view-reports');

            // Export routes
            Route::post('/income-statement/export', [ReportController::class, 'exportIncomeStatement'])
                ->name('admin.reports.income-statement.export')
                ->middleware('can:view-financial-reports');

            Route::post('/balance-sheet/export', [ReportController::class, 'exportBalanceSheet'])
                ->name('admin.reports.balance-sheet.export')
                ->middleware('can:view-financial-reports');

            Route::post('/aged-receivables/export', [ReportController::class, 'exportAgedReceivables'])
                ->name('admin.reports.aged-receivables.export')
                ->middleware('can:view-financial-reports');

            Route::post('/aged-payables/export', [ReportController::class, 'exportAgedPayables'])
                ->name('admin.reports.aged-payables.export')
                ->middleware('can:view-financial-reports');

            Route::post('/adjustment-log/export', [ReportController::class, 'exportAdjustmentLog'])
                ->name('admin.reports.adjustment-log.export')
                ->middleware('can:view-reports');

            Route::post('/recent-transactions/export', [ReportController::class, 'exportRecentTransactions'])
                ->name('admin.reports.recent-transactions.export')
                ->middleware('can:view-reports');

            Route::post('/recent-transactions/bulk-export', [ReportController::class, 'bulkExport'])
                ->name('admin.reports.recent-transactions.bulk-export')
                ->middleware('can:view-reports');

            Route::post('/{id}/mark-paid', [ReportController::class, 'markAsPaid'])
                ->name('admin.transactions.mark-paid')
                ->middleware('can:edit-transactions');

            Route::post('/bulk-mark-paid', [ReportController::class, 'bulkMarkAsPaid'])
                ->name('admin.transactions.bulk-mark-paid')
                ->middleware('can:edit-transactions');
        });

        // Notification Routes
        Route::prefix('notifications')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
            Route::get('/list', [NotificationController::class, 'getNotifications'])->name('admin.notifications.list');
            Route::post('/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read');
            Route::get('/count', [NotificationController::class, 'count'])->name('admin.notifications.count');
            Route::get('/view/{id}', [NotificationController::class, 'view'])->name('admin.notifications.view');
        });

        // User Management Routes
        Route::middleware(['role:superuser'])->group(function () {
            Route::resource('users', UserController::class)->names('admin.users');
        });

        Route::get('api/settings', [SettingsController::class, 'getSettings'])->name('admin.api.settings');

        // Settings Routes
        Route::prefix('settings')->group(function () {
            // Currency Settings
            Route::prefix('currency')->middleware(['role:superuser'])->group(function () {
                Route::get('/', [CurrencyController::class, 'edit'])->name('admin.setting.currency.edit');
                Route::post('/update', [CurrencyController::class, 'update'])->name('admin.setting.currency.update');
            });

            // Profile Settings
            Route::prefix('profile')->group(function () {
                Route::get('/', [ProfileController::class, 'edit'])->name('admin.setting.profile.edit');
                Route::put('/update', [ProfileController::class, 'update'])->name('admin.setting.profile.update');
                Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('admin.setting.profile.update-password');
                Route::delete('/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.setting.profile.delete-avatar');
                Route::post('/token', [ProfileController::class, 'generateApiToken'])->name('admin.setting.profile.token');
            });

            // Tax Settings
            Route::prefix('tax')->group(function () {
                Route::get('/', [TaxController::class, 'index'])->name('admin.setting.tax');
                Route::post('/update', [TaxController::class, 'update'])->name('admin.setting.tax.update');
                Route::get('/get', function () {
                    $tax = \App\Models\Tax::where('is_active', 1)->first();
                    return response()->json(['tax_rate' => $tax ? $tax->rate : 0]);
                })->name('admin.setting.tax.get');
            });

            // Category Settings
            Route::prefix('categories')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('admin.setting.category');
                Route::get('/create', [CategoryController::class, 'create'])->name('admin.setting.category.create');
                Route::post('/store', [CategoryController::class, 'store'])->name('admin.setting.category.store');
                Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('admin.setting.category.edit');
                Route::put('/update/{id}', [CategoryController::class, 'update'])->name('admin.setting.category.update');
                Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('admin.setting.category.destroy');
            });

            // Unit Settings
            Route::prefix('units')->group(function () {
                Route::get('/', [UnitController::class, 'index'])->name('admin.setting.unit');
                Route::get('/create', [UnitController::class, 'create'])->name('admin.setting.unit.create');
                Route::post('/store', [UnitController::class, 'store'])->name('admin.setting.unit.store');
                Route::get('/edit/{id}', [UnitController::class, 'edit'])->name('admin.setting.unit.edit');
                Route::put('/update/{id}', [UnitController::class, 'update'])->name('admin.setting.unit.update');
                Route::delete('/destroy/{id}', [UnitController::class, 'destroy'])->name('admin.setting.unit.destroy');
            });

            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.setting.notifications');

            Route::get('/', [SettingsController::class, 'index'])->name('admin.setting.index');
            Route::put('/', [SettingsController::class, 'update'])->name('admin.setting.update');
            Route::put('/update-theme-mode', [SettingsController::class, 'updateThemeMode'])->name('admin.setting.update-theme-mode');
            Route::get('/accounting', [\App\Http\Controllers\Admin\AccountingController::class, 'accounting'])->name('admin.setting.accounting');
            Route::post('/accounting', [\App\Http\Controllers\Admin\AccountingController::class, 'updateAccounting'])->name('admin.setting.accounting.update');
            Route::post('/reset-coa-default', [\App\Http\Controllers\Admin\AccountingController::class, 'resetToDefault'])->name('admin.setting.reset-coa-default');
        });

        // Accounting Routes
        Route::prefix('accounting')->middleware('can:view-accounting')->group(function () {
            Route::get('/', fn() => redirect()->route('admin.accounting.ledger'));

            Route::get('/chart-of-accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'chartOfAccounts'])
                ->name('admin.accounting.chart')
                ->middleware('can:view-chart-of-accounts');

            Route::get('/journal', [\App\Http\Controllers\Admin\AccountingController::class, 'journal'])
                ->name('admin.accounting.journal')
                ->middleware('can:view-journal');

            Route::get('/general-ledger', [\App\Http\Controllers\Admin\AccountingController::class, 'generalLedger'])
                ->name('admin.accounting.ledger')
                ->middleware('can:view-general-ledger');

            Route::get('/trial-balance', [\App\Http\Controllers\Admin\AccountingController::class, 'trialBalance'])
                ->name('admin.accounting.trial_balance')
                ->middleware('can:view-trial-balance');

            // Export routes
            Route::post('/journal/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportJournal'])
                ->name('admin.accounting.journal.export')
                ->middleware('can:view-journal');

            Route::post('/general-ledger/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportGeneralLedger'])
                ->name('admin.accounting.ledger.export')
                ->middleware('can:view-general-ledger');

            Route::post('/trial-balance/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportTrialBalance'])
                ->name('admin.accounting.trial_balance.export')
                ->middleware('can:view-trial-balance');

            // COA Management
            Route::get('/accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsIndex'])
                ->name('admin.accounting.accounts.index')
                ->middleware('can:view-chart-of-accounts');

            Route::get('/accounts/create', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsCreate'])
                ->name('admin.accounting.accounts.create')
                ->middleware('can:edit-chart-of-accounts');

            Route::post('/accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsStore'])
                ->name('admin.accounting.accounts.store')
                ->middleware('can:edit-chart-of-accounts');

            Route::get('/accounts/{account}/edit', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsEdit'])
                ->name('admin.accounting.accounts.edit')
                ->middleware('can:edit-chart-of-accounts');

            Route::put('/accounts/{account}', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsUpdate'])
                ->name('admin.accounting.accounts.update')
                ->middleware('can:edit-chart-of-accounts');

            Route::delete('/accounts/{account}', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsDestroy'])
                ->name('admin.accounting.accounts.destroy')
                ->middleware('can:delete-chart-of-accounts');

            Route::post('/accounts/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportAll'])
                ->name('admin.accounting.accounts.export')
                ->middleware('can:view-chart-of-accounts');

            // Manual Journal Entry Routes
            Route::prefix('journal-entries')->group(function () {
                Route::get('/', [JournalEntryController::class, 'index'])->name('admin.accounting.journal-entries.index');
                Route::get('/create', [JournalEntryController::class, 'create'])->name('admin.accounting.journal-entries.create');
                Route::post('/', [JournalEntryController::class, 'store'])->name('admin.accounting.journal-entries.store');
                Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('admin.accounting.journal-entries.show');
                Route::get('/{journalEntry}/edit', [JournalEntryController::class, 'edit'])->name('admin.accounting.journal-entries.edit');
                Route::put('/{journalEntry}', [JournalEntryController::class, 'update'])->name('admin.accounting.journal-entries.update');
                Route::post('/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('admin.accounting.journal-entries.post');
                Route::post('/{journalEntry}/void', [JournalEntryController::class, 'void'])->name('admin.accounting.journal-entries.void');
                Route::post('/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('admin.accounting.journal-entries.reverse');
                Route::delete('/{journalEntry}', [JournalEntryController::class, 'destroy'])->name('admin.accounting.journal-entries.destroy');
                Route::post('/{journalEntry}/duplicate', [JournalEntryController::class, 'duplicate'])->name('admin.accounting.journal-entries.duplicate');
                Route::get('/search/accounts', [JournalEntryController::class, 'searchAccounts'])->name('admin.accounting.journal-entries.search-accounts');
                Route::post('/validate', [JournalEntryController::class, 'validateEntry'])->name('admin.accounting.journal-entries.validate');
            });
        });
    });
});

Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.login');
});
