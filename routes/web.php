<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Seller products - restricted to sellers only using our CheckRole middleware
    Route::middleware(['auth', 'check.role:seller'])->group(function () {
        Route::get('/seller/products', [\App\Http\Controllers\Seller\ProductController::class, 'index'])->name('seller.products.index');
        Route::get('/seller/products/create', [\App\Http\Controllers\Seller\ProductController::class, 'create'])->name('seller.products.create');
        Route::post('/seller/products', [\App\Http\Controllers\Seller\ProductController::class, 'store'])->name('seller.products.store');
        Route::get('/seller/products/{product}/edit', [\App\Http\Controllers\Seller\ProductController::class, 'edit'])->name('seller.products.edit');
        Route::put('/seller/products/{product}', [\App\Http\Controllers\Seller\ProductController::class, 'update'])->name('seller.products.update');
    });

});

Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [\App\Http\Controllers\ProductController::class, 'show'])->name('products.show');

Route::get('/ai/health', [AIController::class, 'health']);
