<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\{CategoryController, CustomerController, ProductController, PurchaseController, SalesPipelineController, SupplierController, UnitController, CurrencyController, SalesController, DashboardController, ProfileController, NotificationController, POSController, ReportController, WarehouseController, TaxController, UserController, CustomerCrmController, SupplierCrmController, SettingsController, PurchaseReturnController, SalesReturnController};
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Admin Authentication Routes
Route::middleware('web')->prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        // Register
        Route::get('/register', fn() => view('admin.auth.register'))->name('admin.register');
        Route::post('/register', [RegisteredUserController::class, 'store'])->name('admin.register.post');

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

        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login.post');

        // Forgot Password
        Route::get('/forgot-password', fn() => view('admin.auth.forgot-password'))->name('admin.password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('admin.password.email');

        // Reset Password
        Route::get('/reset-password/{token}', fn($token) => view('admin.auth.reset-password', ['token' => $token]))->name('admin.password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('admin.password.update');
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

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login')->with('success', 'You just logged out!');
    })->middleware('auth')->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['auth', 'verified'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // MOVED: Role-permissions route - accessible to authenticated users
        Route::get('/roles-permissions', [UserController::class, 'getRolePermissions'])->name('admin.roles-permissions');

        // Product Routes
        Route::prefix('product')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('admin.product');
            Route::get('/create', [ProductController::class, 'create'])->name('admin.product.create');
            Route::post('/store', [ProductController::class, 'store'])->name('admin.product.store');
            Route::get('/edit/{id}', [ProductController::class, 'edit'])->name('admin.product.edit');
            Route::get('/view/{id}', [ProductController::class, 'view'])->name('admin.product.view');
            Route::put('/update/{id}', [ProductController::class, 'update'])->name('admin.product.update');
            Route::delete('/destroy/{id}', [ProductController::class, 'destroy'])->name('admin.product.destroy');
            Route::get('/modal-view/{id}', [ProductController::class, 'modalView'])->name('admin.product.modal-view');
            Route::get('/{id}/adjustment-log', [ProductController::class, 'getAdjustmentLog'])->name('admin.product.adjustment-log');
            Route::post('/quick-create', [ProductController::class, 'quickCreate'])->name('admin.product.quickCreate');
            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('product.bulk-delete');
            Route::post('/bulk-export', [ProductController::class, 'bulkExport'])->name('product.bulk-export');
            Route::post('/bulk-stock-details', [ProductController::class, 'bulkStockDetails'])->name('product.bulk-stock-details');
            Route::post('/bulk-update-stock', [ProductController::class, 'bulkUpdateStock'])->name('product.bulk-update-stock');
            Route::post('/adjust-stock', [ProductController::class, 'adjustStock'])->name('admin.product.adjust-stock');
            Route::get('/search', [ProductController::class, 'search'])->name('admin.product.search');
            Route::get('/search-by-barcode', [ProductController::class, 'searchByBarcode'])->name('admin.product.search-by-barcode');
            Route::get('/metrics', [ProductController::class, 'getProductMetrics'])->name('admin.product.metrics');
            Route::get('/expiring-soon', [ProductController::class, 'getExpiringSoonProducts'])->name('admin.product.expiring-soon');
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
            Route::post('/export', [SupplierController::class, 'exportAll'])->name('admin.supplier.export');

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
            Route::post('/export', [CustomerController::class, 'exportAll'])->name('admin.customer.export');
        });

        // CRM Routes
        Route::get('/customers/{id}/crm-details', [CustomerCrmController::class, 'show']);
        Route::post('/customers/{id}/interactions', [CustomerCrmController::class, 'storeInteraction']);
        Route::get('/customers/{id}/historical-purchases', [CustomerCrmController::class, 'getHistoricalPurchases']);
        Route::get('/customers/{id}/product-history', [CustomerCrmController::class, 'getProductHistory']);

        // SRM Routes
        Route::get('/suppliers/{id}/srm-details', [SupplierCrmController::class, 'show']);
        Route::post('/suppliers/{id}/interactions', [SupplierCrmController::class, 'storeInteraction']);
        Route::get('/suppliers/{id}/historical-purchases', [SupplierCrmController::class, 'getHistoricalPurchases']);
        Route::get('/suppliers/{id}/product-history', [SupplierCrmController::class, 'getProductHistory']);

        // Sales Pipeline Routes
        Route::get('/sales-pipeline', [SalesPipelineController::class, 'index'])->name('admin.sales_pipeline.index');

        Route::prefix('sales-pipeline')->group(function () {
            // Pipelines
            Route::get('/pipelines', [SalesPipelineController::class, 'indexPipelines'])->name('admin.sales_pipeline.pipelines.index');
            Route::post('/pipelines', [SalesPipelineController::class, 'storePipeline'])->name('admin.sales_pipeline.pipelines.store');
            Route::put('/pipelines/{pipeline}', [SalesPipelineController::class, 'updatePipeline'])->name('admin.sales_pipeline.pipelines.update');
            Route::delete('/pipelines/{pipeline}', [SalesPipelineController::class, 'destroyPipeline'])->name('admin.sales_pipeline.pipelines.destroy');

            // Stages
            Route::post('/pipelines/{pipeline}/stages', [SalesPipelineController::class, 'storeStage'])->name('admin.sales_pipeline.stages.store');
            Route::put('/stages/{stage}', [SalesPipelineController::class, 'updateStage'])->name('admin.sales_pipeline.stages.update');
            Route::delete('/stages/{stage}', [SalesPipelineController::class, 'destroyStage'])->name('admin.sales_pipeline.stages.destroy');
            Route::post('/pipelines/{pipeline}/stages/reorder', [SalesPipelineController::class, 'reorderStages'])->name('admin.sales_pipeline.stages.reorder');

            // Opportunities
            Route::get('/opportunities', [SalesPipelineController::class, 'indexOpportunities'])->name('admin.sales_pipeline.opportunities.index');
            Route::post('/opportunities', [SalesPipelineController::class, 'storeOpportunity'])->name('admin.sales_pipeline.opportunities.store');
            Route::post('/opportunities/{opportunity}', [SalesPipelineController::class, 'updateOpportunity'])->name('admin.sales_pipeline.opportunities.update');
            Route::get('/opportunities/{opportunity}', [SalesPipelineController::class, 'showOpportunity'])->name('admin.sales_pipeline.opportunities.show');
            Route::delete('/opportunities/{opportunity}', [SalesPipelineController::class, 'destroyOpportunity'])->name('admin.sales_pipeline.opportunities.destroy');
            Route::put('/opportunities/{opportunity}/move', [SalesPipelineController::class, 'moveOpportunity'])->name('admin.sales_pipeline.opportunities.move');
            Route::get('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'showConvertForm'])->name('admin.sales_pipeline.opportunities.convert.show');
            Route::post('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'convertToSalesOrder'])->name('admin.sales_pipeline.opportunities.convert');
            Route::post('/export', [SalesPipelineController::class, 'exportAll'])->name('admin.sales-pipeline.export');
        });

        // Purchase Order Routes
        Route::prefix('po')->group(function () {
            Route::get('/', [PurchaseController::class, 'index'])->name('admin.po');
            Route::get('/create', [PurchaseController::class, 'create'])->name('admin.po.create');
            Route::post('/store', [PurchaseController::class, 'store'])->name('admin.po.store');
            Route::get('/edit/{id}', [PurchaseController::class, 'edit'])->name('admin.po.edit');
            Route::get('/view/{id}', [PurchaseController::class, 'view'])->name('admin.po.view');
            Route::put('/update/{id}', [PurchaseController::class, 'update'])->name('admin.po.update');
            Route::delete('/destroy/{id}', [PurchaseController::class, 'destroy'])->name('admin.po.destroy');
            Route::get('/product/{id}', [PurchaseController::class, 'getProductDetails'])->name('admin.po.product.details');
            Route::get('/modal-view/{id}', [PurchaseController::class, 'modalView'])->name('admin.po.modal-view');
            Route::post('/bulk-delete', [PurchaseController::class, 'bulkDelete'])->name('po.bulk-delete');
            Route::post('/bulk-mark-paid', [PurchaseController::class, 'bulkMarkPaid'])->name('po.bulk-mark-paid');
            Route::post('/bulk-export', [PurchaseController::class, 'bulkExport'])->name('po.bulk-export');
            Route::get('/metrics', [PurchaseController::class, 'getPurchaseMetrics'])->name('admin.po.metrics');
            Route::get('/expiring-soon', [PurchaseController::class, 'getExpiringSoonPurchases'])->name('admin.po.expiring-soon');
            Route::post('/{id}/payment', [PurchaseController::class, 'addPayment'])->name('admin.po.add-payment');
        });

        // Purchase Return Routes
        Route::get('por/purchase/{purchase}', [PurchaseReturnController::class, 'getPurchaseItems'])->name('admin.por.items');
        Route::get('por/{por}/modal-view', [PurchaseReturnController::class, 'modalView'])->name('admin.por.modal-view');
        Route::resource('por', PurchaseReturnController::class)->names('admin.por');
        Route::post('por/bulk-delete', [PurchaseReturnController::class, 'bulkDelete'])->name('admin.por.bulk-delete');
        Route::post('por/bulk-complete', [PurchaseReturnController::class, 'bulkComplete'])->name('admin.por.bulk-complete');
        Route::post('por/bulk-cancel', [PurchaseReturnController::class, 'bulkCancel'])->name('admin.por.bulk-cancel');
Route::post('por/bulk-export', [PurchaseReturnController::class, 'bulkExport'])->name('admin.por.bulk-export');


        // Sales Routes
        Route::prefix('sales')->group(function () {
            Route::get('/', [SalesController::class, 'index'])->name('admin.sales');
            Route::get('/create', [SalesController::class, 'create'])->name('admin.sales.create');
            Route::post('/store', [SalesController::class, 'store'])->name('admin.sales.store');
            Route::get('/edit/{id}', [SalesController::class, 'edit'])->name('admin.sales.edit');
            Route::get('/view/{id}', [SalesController::class, 'view'])->name('admin.sales.view');
            Route::put('/update/{id}', [SalesController::class, 'update'])->name('admin.sales.update');
            Route::delete('/destroy/{id}', [SalesController::class, 'destroy'])->name('admin.sales.destroy');
            Route::get('/product/{id}', [SalesController::class, 'getInvoiceDetails'])->name('admin.sales.product.details');
            Route::get('/modal-view/{id}', [SalesController::class, 'modalViews'])->name('admin.sales.modal-view');
            Route::post('/bulk-delete', [SalesController::class, 'bulkDelete'])->name('sales.bulk-delete');
            Route::post('/bulk-mark-paid', [SalesController::class, 'bulkMarkPaid'])->name('sales.bulk-mark-paid');
            Route::post('/bulk-export', [SalesController::class, 'bulkExport'])->name('sales.bulk-export');
            Route::get('/get-customer-price/{customer}/{product}', [SalesController::class, 'getCustomerPrice'])->name('admin.sales.get-customer-price');
            Route::get('/metrics', [SalesController::class, 'getSalesMetrics'])->name('admin.sales.metrics');
            Route::get('/expiring-soon', [SalesController::class, 'getExpiringSoonSales'])->name('admin.sales.expiring-soon');
            Route::post('/{id}/payment', [SalesController::class, 'addPayment'])->name('admin.sales.add-payment');
        });

        // Sales Return Routes
        Route::get('sales-returns/sale/{sale}', [SalesReturnController::class, 'getSalesItems'])->name('admin.sales-returns.items');
        Route::get('sales-returns/{salesReturn}/modal-view', [SalesReturnController::class, 'modalView'])->name('admin.sales-returns.modal-view');
        Route::resource('sales-returns', SalesReturnController::class)->names('admin.sales-returns');
        Route::post('sales-returns/bulk-delete', [SalesReturnController::class, 'bulkDelete'])->name('admin.sales-returns.bulk-delete');
        Route::post('sales-returns/bulk-complete', [SalesReturnController::class, 'bulkComplete'])->name('admin.sales-returns.bulk-complete');
        Route::post('sales-returns/bulk-cancel', [SalesReturnController::class, 'bulkCancel'])->name('admin.sales-returns.bulk-cancel');
Route::post('sales-returns/bulk-export', [SalesReturnController::class, 'bulkExport'])->name('admin.sales-returns.bulk-export');


        // Warehouse Routes
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index'])->name('admin.warehouse');
            Route::get('/create', [WarehouseController::class, 'create'])->name('admin.warehouse.create');
            Route::post('/store', [WarehouseController::class, 'store'])->name('admin.warehouse.store');
            Route::get('/edit/{id}', [WarehouseController::class, 'edit'])->name('admin.warehouse.edit');
            Route::get('/view/{id}', [WarehouseController::class, 'view'])->name('admin.warehouse.view');
            Route::put('/update/{id}', [WarehouseController::class, 'update'])->name('admin.warehouse.update');
            Route::delete('/destroy/{id}', [WarehouseController::class, 'destroy'])->name('admin.warehouse.destroy');
            Route::get('/{id}/set-main', [WarehouseController::class, 'setMain'])->name('admin.warehouse.set-main');
            Route::get('/{id}/unset-main', [WarehouseController::class, 'unsetMain'])->name('admin.warehouse.unset-main');
            Route::post('/export', [WarehouseController::class, 'exportAll'])->name('admin.warehouse.export');
        });

        // POS Routes
        Route::prefix('pos')->group(function () {
            Route::get('/', [POSController::class, 'index'])->name('admin.pos');
            Route::get('/create', [POSController::class, 'create'])->name('admin.pos.create');
            Route::post('/store', [POSController::class, 'store'])->name('admin.pos.store');
            Route::get('/edit/{id}', [POSController::class, 'edit'])->name('admin.pos.edit');
            Route::get('/view/{id}', [POSController::class, 'view'])->name('admin.pos.view');
            Route::put('/update/{id}', [POSController::class, 'update'])->name('admin.pos.update');
            Route::delete('/destroy/{id}', [POSController::class, 'destroy'])->name('admin.pos.destroy');
            Route::get('/receipt/{id}', [POSController::class, 'receipt'])->name('admin.pos.receipt');
            Route::get('/print-receipt/{id}', [POSController::class, 'printReceipt'])->name('admin.pos.print-receipt');
        });

        // Reports Routes
        Route::prefix('reports')->group(function () {
            Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->name('admin.reports.income-statement');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('admin.reports.balance-sheet');
            Route::get('/aged-receivables', [ReportController::class, 'agedReceivables'])->name('admin.reports.aged-receivables');
            Route::get('/aged-payables', [ReportController::class, 'agedPayables'])->name('admin.reports.aged-payables');
            Route::get('/adjustment-log', [ReportController::class, 'adjustmentLog'])->name('admin.reports.adjustment-log');
            Route::get('/recent-transactions', [ReportController::class, 'recentTransactions'])->name('admin.reports.recent-transactions');
            Route::post('/income-statement/export', [ReportController::class, 'exportIncomeStatement'])->name('admin.reports.income-statement.export');
            Route::post('/balance-sheet/export', [ReportController::class, 'exportBalanceSheet'])->name('admin.reports.balance-sheet.export');
            Route::post('/aged-receivables/export', [ReportController::class, 'exportAgedReceivables'])->name('admin.reports.aged-receivables.export');
            Route::post('/{id}/mark-paid', [ReportController::class, 'markAsPaid'])->name('admin.transactions.mark-paid');
            Route::post('/bulk-mark-paid', [ReportController::class, 'bulkMarkAsPaid'])->name('admin.transactions.bulk-mark-paid');
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
                Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('admin.setting.profile.update-password')->middleware('password.confirm');
                Route::put('/update-password', [ProfileController::class, 'updatePassword'])->name('admin.setting.profile.update-password')->middleware('password.confirm');
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
            Route::post('/apply-coa-template', [\App\Http\Controllers\Admin\AccountingController::class, 'applyCoaTemplate'])->name('admin.setting.apply-coa-template');
        });

        // Accounting Routes
        Route::prefix('accounting')->group(function () {
            Route::get('/', fn() => redirect()->route('admin.accounting.ledger'));
            Route::get('/chart-of-accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'chartOfAccounts'])->name('admin.accounting.chart');
            Route::get('/journal', [\App\Http\Controllers\Admin\AccountingController::class, 'journal'])->name('admin.accounting.journal');
            Route::get('/general-ledger', [\App\Http\Controllers\Admin\AccountingController::class, 'generalLedger'])->name('admin.accounting.ledger');
            Route::get('/trial-balance', [\App\Http\Controllers\Admin\AccountingController::class, 'trialBalance'])->name('admin.accounting.trial_balance');
            Route::post('/journal/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportJournal'])->name('admin.accounting.journal.export');
            Route::post('/general-ledger/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportGeneralLedger'])->name('admin.accounting.ledger.export');
            Route::post('/trial-balance/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportTrialBalance'])->name('admin.accounting.trial_balance.export');
            
            // COA Management
            Route::get('/accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsIndex'])->name('admin.accounting.accounts.index');
            Route::get('/accounts/create', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsCreate'])->name('admin.accounting.accounts.create');
            Route::post('/accounts', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsStore'])->name('admin.accounting.accounts.store');
            Route::get('/accounts/{account}/edit', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsEdit'])->name('admin.accounting.accounts.edit');
            Route::put('/accounts/{account}', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsUpdate'])->name('admin.accounting.accounts.update');
            Route::delete('/accounts/{account}', [\App\Http\Controllers\Admin\AccountingController::class, 'accountsDestroy'])->name('admin.accounting.accounts.destroy');
            Route::post('/accounts/export', [\App\Http\Controllers\Admin\AccountingController::class, 'exportAll'])->name('admin.accounting.accounts.export');
        });
    });
});

Route::get('/', function () {
    if (Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.login');
});
