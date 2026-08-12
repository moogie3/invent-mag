<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\TenantLookupController;
use App\Http\Controllers\Api\Webhook\MidtransController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Essential Endpoints Only
|--------------------------------------------------------------------------
|
| These routes are used by the Astro frontend for tenant lookup and
| authentication. All other functionality uses the web admin panel.
|
*/

// CMS content (public, no auth required)
Route::get('/cms', [CmsController::class, 'show'])->name('api.cms.show');

// Public pricing data for the marketing site (no auth required)
Route::get('/plans', [PlanController::class, 'index'])->name('api.plans.public');

// Webhooks (Must be outside of CSRF and auth middleware)
Route::post('/webhooks/midtrans', [MidtransController::class, 'handle'])->name('api.webhooks.midtrans');
Route::post('/webhook', [MidtransController::class, 'handleFallback']); // Fallback for misconfigured Midtrans dashboard

// Payment confirmation - requires authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payment/confirm', [PaymentController::class, 'confirm'])->name('api.payment.confirm');
});

// Public routes - Authentication & Tenant Lookup
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:3,60'); // Only 3 registrations per hour per IP
});

Route::post('/lookup-tenant', [TenantLookupController::class, 'lookup'])->middleware('throttle:10,1');

// Contact form - public, rate-limited (marketing site)
Route::post('/contact', [ContactController::class, 'submit'])
    ->middleware('throttle:5,60')
    ->name('api.contact');

// Protected routes - Token management only
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::post('/refresh-token', [AuthController::class, 'refresh'])->name('api.refresh-token');

    Route::get('/plans', [PlanController::class, 'authenticatedIndex'])->name('api.plans.index');

    Route::get('/plan/current', [PlanController::class, 'current'])->name('api.plan.current');
});
