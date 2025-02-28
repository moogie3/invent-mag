<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\{
    CategoryController, CustomerController, ProductController, PurchaseController,
    SupplierController, UnitController, CurrencyController, DailySalesController, SalesController, DashboardController
};
use App\Models\SalesItem;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

// Fortify Authentication Views
Fortify::registerView(fn () => view('admin.auth.register'));
Route::get('/register', fn () => view('admin.auth.register'))->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

// Forgot Password Routes
Route::get('/forgot-password', fn () => view('admin.auth.forgot-password'))->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

// Reset Password Routes
Route::get('/reset-password/{token}', fn ($token) => view('admin.auth.reset-password', ['token' => $token]))->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');

// Authentication Routes
Route::get('/login', fn () => view('admin.auth.login'))->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.post');
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Admin Routes
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    $resources = [
        'product' => ProductController::class,
        'category' => CategoryController::class,
        'unit' => UnitController::class,
        'supplier' => SupplierController::class,
        'customer' => CustomerController::class,
        'po' => PurchaseController::class,
        'sales' => SalesController::class,
        'ds' => DailySalesController::class
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

    Route::get('po/product/{id}', [PurchaseController::class, 'getProductDetails'])->name('product.details');
    Route::get('sales/product/{id}', [SalesController::class, 'getInvoiceDetails'])->name('product.details');

    // Settings
    Route::prefix('setting')->group(function () {
        Route::get('/', [CurrencyController::class, 'edit'])->name('admin.currency.edit');
        Route::post('/edit', [CurrencyController::class, 'update'])->name('admin.currency.update');
    });

    Route::get('/sales/get-past-price', [SalesController::class, 'getPastPrice']);

});