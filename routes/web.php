<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\{CategoryController, CustomerController, ProductController, PurchaseController, SupplierController, UnitController, CurrencyController, SalesController, DashboardController, ProfileController, NotificationController, POSController, ReportController, WarehouseController, TaxController, TransactionController, UserController, CustomerCrmController};
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Admin Authentication Routes
Route::prefix('admin')->group(function () {
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

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login')->with('success', 'You just logged out!');
    })->name('admin.logout');

    // Forgot Password
    Route::get('/forgot-password', fn() => view('admin.auth.forgot-password'))->name('admin.password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('admin.password.email');

    // Reset Password
    Route::get('/reset-password/{token}', fn($token) => view('admin.auth.reset-password', ['token' => $token]))->name('admin.password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('admin.password.update');

    // Protected Admin Routes
    Route::middleware('auth')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

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
            Route::post('/quick-create', [ProductController::class, 'quickCreate'])->name('admin.product.quickCreate');
            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete'])->name('product.bulk-delete');
            Route::post('/bulk-export', [ProductController::class, 'bulkExport'])->name('product.bulk-export');
            Route::post('/bulk-stock-details', [ProductController::class, 'bulkStockDetails'])->name('product.bulk-stock-details');
            Route::post('/bulk-update-stock', [ProductController::class, 'bulkUpdateStock'])->name('product.bulk-update-stock');
            Route::get('/search', [ProductController::class, 'search'])->name('admin.product.search');
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
        });

        // CRM Routes
        Route::get('/customers/{id}/crm-details', [CustomerCrmController::class, 'show']);
        Route::post('/customers/{id}/interactions', [CustomerCrmController::class, 'storeInteraction']);

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
        });

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
        });

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
        });

        // Transaction Routes
        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionController::class, 'index'])->name('admin.transactions');
            Route::post('/{id}/mark-paid', [TransactionController::class, 'markAsPaid'])->name('admin.transactions.mark-paid');
            Route::post('/bulk-mark-paid', [TransactionController::class, 'bulkMarkAsPaid']);
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

        // Settings Routes
        Route::prefix('settings')->group(function () {
            // Currency Settings
            Route::prefix('currency')->group(function () {
                Route::get('/', [CurrencyController::class, 'edit'])->name('admin.setting.currency.edit');
                Route::post('/update', [CurrencyController::class, 'update'])->name('admin.setting.currency.update');
            });

            // Profile Settings
            Route::prefix('profile')->group(function () {
                Route::get('/', [ProfileController::class, 'edit'])->name('admin.setting.profile.edit');
                Route::put('/update', [ProfileController::class, 'update'])->name('admin.setting.profile.update');
                Route::delete('/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.setting.profile.delete-avatar');
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
        });
    });
});

Route::get('/', function () {
    return redirect('/admin/login');
});