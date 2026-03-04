<?php
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\TenantLookupController;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Webhook\MidtransController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes - Essential Endpoints Only
|--------------------------------------------------------------------------
|
| These routes are used by the Astro frontend for tenant lookup and
| authentication. All other functionality uses the web admin panel.
|
*/

// Webhooks (Must be outside of CSRF and auth middleware)
Route::post('/webhooks/midtrans', [MidtransController::class, 'handle'])->name('api.webhooks.midtrans');

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

// Protected routes - Token management only
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::post('/refresh-token', function (Request $request) {
        $user = $request->user();

        // Revoke old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token', ['*'], now()->addDay())->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_at' => now()->addDay()->toISOString(),
        ]);
    })->name('api.refresh-token');

    // Plan endpoints
    Route::get('/plans', function () {
        return response()->json(Plan::active()->ordered()->get());
    })->name('api.plans.index');

    Route::get('/plan/current', function (Request $request) {
        $planService = app(PlanService::class);
        return response()->json($planService->getUsageStats());
    })->name('api.plan.current');
});
