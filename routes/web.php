<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\{
    CategoryController, CustomerController, ProductController, PurchaseController,
    SupplierController, UnitController, CurrencyController, DailySalesController,
    SalesController, DashboardController, ProfileController, NotificationController,
    POSController, WarehouseController,
};
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Admin Authentication Routes (Move Inside "admin" Prefix)
Route::prefix('admin')->group(function () {
    // Register
    Route::get('/register', fn () => view('admin.auth.register'))->name('admin.register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('admin.register.post');

    // Login
    Route::get('/login', fn () => view('admin.auth.login'))->name('admin.login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login.post');

    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login')->with('success', 'You just logged out!');
    })->name('admin.logout');

    // Forgot Password
    Route::get('/forgot-password', fn () => view('admin.auth.forgot-password'))->name('admin.password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('admin.password.email');

    // Reset Password
    Route::get('/reset-password/{token}', fn ($token) => view('admin.auth.reset-password', ['token' => $token]))->name('admin.password.reset');
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
            'ds' => DailySalesController::class,
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

        // Settings
        Route::prefix('setting')->group(function () {
            Route::get('/currency', [CurrencyController::class, 'edit'])->name('admin.setting.currency.edit');
            Route::post('/currency/update', [CurrencyController::class, 'update'])->name('admin.setting.currency.update');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.setting.profile.edit');
            Route::put('/profile/update', [ProfileController::class, 'update'])->name('admin.setting.profile.update');
            Route::delete('/profile/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.setting.profile.delete-avatar');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.setting.notifications');
            Route::get('/notifications/count', [NotificationController::class, 'count'])->name('admin.notifications.count');

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

        Route::get('/sales/get-past-price', [SalesController::class, 'getPastPrice']);
    });
});

// Redirect root URL to admin login
Route::get('/', function () {
    return redirect('/admin/login');
});