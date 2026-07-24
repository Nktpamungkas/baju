<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ProductAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'home'])->name('home');
Route::get('/katalog', [ProductController::class, 'catalog'])->name('catalog');
Route::get('/produk/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/tentang', [ProductController::class, 'about'])->name('about');

Route::get('/keranjang', [CartController::class, 'index'])->name('cart');
Route::post('/checkout', [CartController::class, 'checkout'])->name('checkout');

/*
|--- Admin ---------------------------------------------------------------
| Login sederhana (password di .env: ADMIN_PASSWORD). Pesanan tetap via
| marketplace; admin hanya kelola katalog + lihat order dari checkout.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [ProductAdminController::class, 'index'])->name('products');
        Route::get('pesanan', [ProductAdminController::class, 'orders'])->name('orders');
        Route::post('produk', [ProductAdminController::class, 'store'])->name('products.store');
        Route::put('produk/{product}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::delete('produk/{product}', [ProductAdminController::class, 'destroy'])->name('products.destroy');
        Route::post('upload', [ProductAdminController::class, 'upload'])->name('upload');
    });
});
