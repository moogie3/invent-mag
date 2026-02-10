<?php
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\TenantLookupController;
use Illuminate\Http\Request;
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
});
