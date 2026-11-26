<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Mobile\DashboardController;
use App\Http\Controllers\Api\Mobile\PosController;
use App\Http\Controllers\Api\Mobile\ProductController;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Minimal public API. Session-based auth covers the web admin panel.
|
*/

// CMS content (public, no auth required)
Route::get('/cms', [CmsController::class, 'show'])->name('api.cms.show');

// Public routes - Authentication
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Contact form - public, rate-limited
Route::post('/contact', [ContactController::class, 'submit'])
    ->middleware('throttle:5,60')
    ->name('api.contact');

// Protected routes - Token management only
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('api.refresh-token');
});

// Mobile PWA endpoints — thin wrappers over the same services the admin
// panel uses, so POS/dashboard numbers can never drift between surfaces.
Route::middleware(['auth:sanctum', 'throttle:api', NeedsTenant::class])
    ->prefix('v1/mobile')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('can:access-dashboard')
            ->name('api.mobile.dashboard');

        // POS
        Route::get('/pos/products', [PosController::class, 'searchProducts'])
            ->middleware('can:access-pos');
        Route::get('/pos/taxes', [PosController::class, 'taxes'])
            ->middleware('can:access-pos');
        Route::post('/pos/sales', [PosController::class, 'store'])
            ->middleware('can:access-pos');
        Route::post('/pos/validate-stock', [PosController::class, 'validateStock'])
            ->middleware('can:access-pos');
        Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])
            ->middleware('can:access-pos');

        // Product lookup
        Route::get('/products/search', [ProductController::class, 'search'])
            ->middleware('can:view-products');
        Route::get('/products/barcode', [ProductController::class, 'searchByBarcode'])
            ->middleware('can:view-products');
        Route::get('/products/{id}', [ProductController::class, 'show'])
            ->middleware('can:view-products');
    });
