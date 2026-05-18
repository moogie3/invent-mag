<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

// Tutorial Skip
Route::post('/tutorial/skip', function (Request $request) {
    $request->user()->update(['tutorial_completed' => true]);
    return response()->json(['success' => true]);
})->name('admin.tutorial.skip');

// Tutorial context endpoint - returns rendered context step HTML for a given route name
Route::get('/tutorial/context', function (Request $request) {
    $routeName = $request->query('route', '');
    return response()->view('admin.layouts.modals.general.tutorial-context', [
        'routeName' => $routeName,
    ]);
})->name('admin.tutorial.context');

// Role-permissions route - accessible only to superuser
Route::get('/roles-permissions', [UserController::class, 'getRolePermissions'])
    ->name('admin.roles-permissions')
    ->middleware('role:superuser');
