<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;

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
        Route::delete('/seller/products/{product}', [\App\Http\Controllers\Seller\ProductController::class, 'destroy'])->name('seller.products.destroy');
        
        // Seller orders
        Route::get('/seller/orders', [\App\Http\Controllers\Seller\OrderController::class, 'dashboard'])->name('seller.orders');
        Route::get('/seller/orders/list', [\App\Http\Controllers\Seller\OrderController::class, 'index'])->name('seller.orders.index');
        Route::get('/seller/orders/{order}', [\App\Http\Controllers\Seller\OrderController::class, 'show'])->name('seller.orders.show');
        Route::post('/seller/orders/{order}/continue', [\App\Http\Controllers\Seller\OrderController::class, 'continue'])->name('seller.orders.continue');
        Route::post('/seller/orders/{order}/cancel', [\App\Http\Controllers\Seller\OrderController::class, 'cancel'])->name('seller.orders.cancel');
    });

    Route::middleware(['auth', 'check.role:admin'])->group(function () {
        //Product management for admin
        Route::get('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('admin.products.index');
        Route::get('/admin/products/create', [\App\Http\Controllers\Admin\ProductController::class, 'create'])->name('admin.products.create');
        Route::post('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])->name('admin.products.store');
        Route::get('/admin/products/{product}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('admin.products.edit');
        Route::put('/admin/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('admin.products.update');
        Route::delete('/admin/products/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('admin.products.destroy');
        // AI Dashboard routes
        Route::get('/admin/ai-dashboard', [AIController::class, 'dashboard'])->name('admin.ai-dashboard');
    });

});

    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [\App\Http\Controllers\ProductController::class, 'show'])->name('products.show');

    // Cart Routes
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/update', [CartController::class, 'update'])->name('cart.update');
        Route::post('/remove', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
    });

    // Notification Routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // Order Routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        
        // Review Routes
        Route::get('/{order}/review', [\App\Http\Controllers\ReviewController::class, 'show'])->name('reviews.show');
        Route::post('/{order}/review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
        Route::post('/reviews/{review}/answer', [\App\Http\Controllers\ReviewController::class, 'answer'])->name('reviews.answer');
    });

    Route::get('/ai/health', [AIController::class, 'health']);
