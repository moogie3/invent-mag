<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UnitController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin'], function (){
    Route::view('/', 'admin.dashboard')->name('admin.dashboard');

    Route::group(['prefix' => 'product'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('admin.product');
        Route::get('/create', [ProductController::class, 'create'])->name('admin.product.create');
        Route::post('/store', [ProductController::class, 'store'])->name('admin.product.store');
        Route::get('/edit/{id}', [ProductController::class, 'edit'])->name('admin.product.edit');
        Route::put('/update/{id}', [ProductController::class, 'update'])->name('admin.product.update');
        Route::delete('/destroy/{id}', [ProductController::class, 'destroy'])->name('admin.product.destroy');
    });

    Route::group(['prefix' => 'category'], function () {
        Route::get('/', [CategoryController::class, 'index'])->name('admin.category');
        Route::get('/create', [CategoryController::class, 'create'])->name('admin.category.create');
        Route::post('/store', [CategoryController::class, 'store'])->name('admin.category.store');
        Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('admin.category.edit');
        Route::put('/update/{id}', [CategoryController::class, 'update'])->name('admin.category.update');
        Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('admin.category.destroy');
    });

    Route::group(['prefix' => 'unit'], function () {
        Route::get('/', [UnitController::class, 'index'])->name('admin.unit');
        Route::get('/create', [UnitController::class, 'create'])->name('admin.unit.create');
        Route::post('/store', [UnitController::class, 'store'])->name('admin.unit.store');
        Route::get('/edit/{id}', [UnitController::class, 'edit'])->name('admin.unit.edit');
        Route::put('/update/{id}', [UnitController::class, 'update'])->name('admin.unit.update');
        Route::delete('/destroy/{id}', [UnitController::class, 'destroy'])->name('admin.unit.destroy');
    });

    Route::group(['prefix' => 'supplier'], function () {
        Route::get('/', [SupplierController::class, 'index'])->name('admin.supplier');
        Route::get('/create', [SupplierController::class, 'create'])->name('admin.supplier.create');
        Route::post('/store', [SupplierController::class, 'store'])->name('admin.supplier.store');
        Route::get('/edit/{id}', [SupplierController::class, 'edit'])->name('admin.supplier.edit');
        Route::put('/update/{id}', [SupplierController::class, 'update'])->name('admin.supplier.update');
        Route::delete('/destroy/{id}', [SupplierController::class, 'destroy'])->name('admin.supplier.destroy');
    });

    Route::group(['prefix' => 'customer'], function () {
        Route::get('/', [CustomerController::class, 'index'])->name('admin.customer');
        Route::get('/create', [CustomerController::class, 'create'])->name('admin.customer.create');
        Route::post('/store', [CustomerController::class, 'store'])->name('admin.customer.store');
        Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('admin.customer.edit');
        Route::put('/update/{id}', [CustomerController::class, 'update'])->name('admin.customer.update');
        Route::delete('/destroy/{id}', [CustomerController::class, 'destroy'])->name('admin.customer.destroy');
    });

    Route::group(['prefix' => 'po'], function () {
        Route::get('/', [PurchaseController::class, 'index'])->name('admin.po');
        Route::get('/create', [PurchaseController::class, 'create'])->name('admin.po.create');
        Route::post('/store', [PurchaseController::class, 'store'])->name('admin.po.store');
        Route::get('/edit/{id}', [PurchaseController::class, 'edit'])->name('admin.po.edit');
        Route::put('/update/{id}', [PurchaseController::class, 'update'])->name('admin.po.update');
        Route::delete('/destroy/{id}', [PurchaseController::class, 'destroy'])->name('admin.po.destroy');
        Route::get('/product/{id}', [PurchaseController::class, 'getProductDetails'])->name('product.details');

    });
});