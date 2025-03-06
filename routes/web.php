<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\{
    CategoryController, CustomerController, ProductController, PurchaseController,
    SupplierController, UnitController, CurrencyController, DailySalesController,
    SalesController, DashboardController, ProfileController, NotificationController
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
            'category' => CategoryController::class,
            'unit' => UnitController::class,
            'supplier' => SupplierController::class,
            'customer' => CustomerController::class,
            'po' => PurchaseController::class,
            'sales' => SalesController::class,
            'ds' => DailySalesController::class,
        ];

        foreach ($resources as $route => $controller) {
            Route::prefix($route)->group(function () use ($route, $controller) {
                Route::get('/', [$controller, 'index'])->name("admin.$route");
                Route::get('/create', [$controller, 'create'])->name("admin.$route.create");
                Route::post('/store', [$controller, 'store'])->name("admin.$route.store");
                Route::get('/edit/{id}', [$controller, 'edit'])->name("admin.$route.edit");
                Route::put('/update/{id}', [$controller, 'update'])->name("admin.$route.update");
                Route::delete('/destroy/{id}', [$controller, 'destroy'])->name("admin.$route.destroy");
            });
        }

        Route::get('po/product/{id}', [PurchaseController::class, 'getProductDetails'])->name('admin.product.details');
        Route::get('sales/product/{id}', [SalesController::class, 'getInvoiceDetails'])->name('admin.product.details');

        // Settings
        Route::prefix('setting')->group(function () {
            Route::get('/currency', [CurrencyController::class, 'edit'])->name('admin.currency.edit');
            Route::post('/currency/update', [CurrencyController::class, 'update'])->name('admin.currency.update');
            Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile.edit');
            Route::put('/profile/update', [ProfileController::class, 'update'])->name('admin.profile.update');
            Route::delete('/profile/delete-avatar', [ProfileController::class, 'deleteAvatar'])->name('admin.profile.delete-avatar');
            Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
            Route::get('/notifications/count', [NotificationController::class, 'count'])->name('admin.notifications.count');
        });

        Route::get('/sales/get-past-price', [SalesController::class, 'getPastPrice']);
    });
});