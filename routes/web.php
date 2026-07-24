<?php

use App\Domains\Auth\Controller\AuthController;
use App\Domains\Product\Controller\ProductAdminController;
use App\Domains\Product\Controller\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'home'])->name('home');
Route::get('/katalog', [ProductController::class, 'catalog'])->name('catalog');
Route::get('/produk/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/tentang', [ProductController::class, 'about'])->name('about');

/*
|--- Admin ---------------------------------------------------------------
| Login sederhana (password di .env: ADMIN_PASSWORD). Checkout selalu lewat
| marketplace; admin hanya kelola katalog + link Shopee/Tokopedia.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [ProductAdminController::class, 'index'])->name('products');
        Route::post('produk', [ProductAdminController::class, 'store'])->name('products.store');
        Route::put('produk/{product}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::delete('produk/{product}', [ProductAdminController::class, 'destroy'])->name('products.destroy');
        Route::post('upload', [ProductAdminController::class, 'upload'])->name('upload');
    });
});
