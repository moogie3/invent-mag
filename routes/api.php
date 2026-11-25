<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

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
