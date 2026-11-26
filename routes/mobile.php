<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile PWA routes
|--------------------------------------------------------------------------
|
| Server-rendered shells for the installable PWA. They carry no data —
| every number comes from the /api/v1/mobile/* JSON endpoints, so the
| pages themselves stay public and the Sanctum bearer token (kept in
| localStorage) does the authenticating client-side. That also lets the
| service worker cache the shells for offline startup.
|
*/

Route::middleware('web')->prefix('m')->group(function () {
    Route::get('/', fn () => view('mobile.dashboard'))->name('mobile.dashboard');
    Route::get('/login', fn () => view('mobile.login'))->name('mobile.login');
    Route::get('/pos', fn () => view('mobile.pos'))->name('mobile.pos');
    Route::get('/products', fn () => view('mobile.products'))->name('mobile.products');
});
