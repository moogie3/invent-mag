<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\{CategoryController, CustomerController, ProductController, PurchaseController, SupplierController, UnitController, CurrencyController, SalesController, DashboardController, ProfileController, NotificationController, POSController, ReportController, WarehouseController, TaxController};
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Admin Authentication Routes (Move Inside "admin" Prefix)
Route::prefix('admin')->group(function () {
    // Register
    Route::get('/register', fn() => view('admin.auth.register'))->name('admin.register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('admin.register.post');
    // Login
    Route::get('/login', function (Request $request) {
        // Check if there's an attempted email in the session
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

    // Protected Admin Routes (Require Authentication)
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        $resources = [
            'product' => ProductController::class,
            'supplier' => SupplierController::class,
            'customer' => CustomerController::class,
            'po' => PurchaseController::class,
            'sales' => SalesController::class,
            'warehouse' => WarehouseController::class,
            'pos' => POSController::class,
        ];

        foreach ($resources as $route => $controller) {
            Route::prefix($route)->group(function () use ($route, $controller) {
                Route::get('/', [$controller, 'index'])->name("admin.$route");
                Route::get('/create', [$controller, 'create'])->name("admin.$route.create");
                Route::post('/store', [$controller, 'store'])->name("admin.$route.store");
                Route::get('/edit/{id}', [$controller, 'edit'])->name("admin.$route.edit");
                Route::get('/view/{id}', [$controller, 'view'])->name("admin.$route.view");
                Route::put('/update/{id}', [$controller, 'update'])->name("admin.$route.update");
                Route::delete('/destroy/{id}', [$controller, 'destroy'])->name("admin.$route.destroy");
            });
        }

        Route::get('po/product/{id}', [PurchaseController::class, 'getProductDetails'])->name('admin.po.product.details');
        Route::get('sales/product/{id}', [SalesController::class, 'getInvoiceDetails'])->name('admin.sales.product.details');
        Route::get('/po/view/{id}', [PurchaseController::class, 'view'])->name('admin.po.view');
        Route::get('/sales/view/{id}', [SalesController::class, 'view'])->name('admin.sales.view');
        Route::get('/sales/get-customer-price/{customer}/{product}', [SalesController::class, 'getCustomerPrice'])->name('admin.sales.get-customer-price');
        Route::post('/customer/quick-create', [CustomerController::class, 'quickCreate'])->name('admin.customer.quickCreate');
        Route::post('/product/quick-create', [ProductController::class, 'quickCreate'])->name('admin.product.quickCreate');
        Route::get('/warehouse/{id}/set-main', [WarehouseController::class, 'setMain'])->name('admin.warehouse.set-main');
        Route::get('/warehouse/{id}/unset-main', [WarehouseController::class, 'unsetMain'])->name('admin.warehouse.unset-main');

        // Settings
        Route::prefix('setting')->group(function () {
            Route::get('/currency', [CurrencyController::class, 'edit'])->name('admin.setting.currency.edit');
            Route::post('/currency/update', [CurrencyController::class, 'update'])->name('admin.setting.currency.update');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.setting.profile.edit');
            Route::put('/profile/update', [ProfileController::class, 'update'])->name('admin.setting.profile.update');
            Route::delete('/profile/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.setting.profile.delete-avatar');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.setting.notifications');
            Route::get('/tax', [TaxController::class, 'index'])->name('admin.setting.tax');
            Route::post('/tax/update', [TaxController::class, 'update'])->name('admin.setting.tax.update');

            Route::prefix('category')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('admin.setting.category');
                Route::get('/create', [CategoryController::class, 'create'])->name('admin.setting.category.create');
                Route::post('/store', [CategoryController::class, 'store'])->name('admin.setting.category.store');
                Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('admin.setting.category.edit');
                Route::put('/update/{id}', [CategoryController::class, 'update'])->name('admin.setting.category.update');
                Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('admin.setting.category.destroy');
            });

            Route::prefix('unit')->group(function () {
                Route::get('/', [UnitController::class, 'index'])->name('admin.setting.unit');
                Route::get('/create', [UnitController::class, 'create'])->name('admin.setting.unit.create');
                Route::post('/store', [UnitController::class, 'store'])->name('admin.setting.unit.store');
                Route::get('/edit/{id}', [UnitController::class, 'edit'])->name('admin.setting.unit.edit');
                Route::put('/update/{id}', [UnitController::class, 'update'])->name('admin.setting.unit.update');
                Route::delete('/destroy/{id}', [UnitController::class, 'destroy'])->name('admin.setting.unit.destroy');
            });
        });

        // Fix: Notification Routes - Make sure they are properly defined with correct path
        Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
        Route::get('/notifications/list', [NotificationController::class, 'getNotifications'])->name('admin.notifications.list');
        Route::post('/notifications/mark-read/{id}', [NotificationController::class, 'markAsRead'])->name('admin.notifications.mark-read');
        Route::get('/notifications/count', [NotificationController::class, 'count'])->name('admin.notifications.count');
        Route::get('/notifications/view/{id}', [NotificationController::class, 'view'])->name('admin.notifications.view');

        Route::get('/pos/receipt/{id}', [POSController::class, 'receipt'])->name('admin.pos.receipt');
        Route::get('/po/modal-view/{id}', [PurchaseController::class, 'modalView'])->name('admin.po.modal-view');
        Route::get('/sales/modal-view/{id}', [SalesController::class,'modalView'])->name('admin.sales.modal-view');
        Route::get('/product/modal-view/{id}', [ProductController::class, 'modalView'])->name('admin.product.modal-view');

        // Define tax API route
        Route::get('/setting/tax/get', function () {
            $tax = \App\Models\Tax::where('is_active', 1)->first();
            return response()->json(['tax_rate' => $tax ? $tax->rate : 0]);
        })->name('admin.setting.tax.get');
    });
});

Route::get('/', function () {
    return redirect('/admin/login');
});
