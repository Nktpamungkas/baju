<?php

use App\Domains\Auth\Controller\AuthController;
use App\Domains\Order\Controller\CheckoutController;
use App\Domains\Order\Controller\OrderAdminController;
use App\Domains\Order\Controller\WebhookController;
use App\Domains\Product\Controller\ProductAdminController;
use App\Domains\Product\Controller\ProductController;
use App\Domains\Review\Controller\ReviewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ProductController::class, 'home'])->name('home');
Route::get('/katalog', [ProductController::class, 'catalog'])->name('catalog');
Route::get('/produk/{product}', [ProductController::class, 'show'])->name('product');
Route::get('/tentang', [ProductController::class, 'about'])->name('about');

/*
|--- Checkout ------------------------------------------------------------
| Keranjang murni di client (lihat resources/js/lib/cart.js). Pembayaran
| aktif saat ini: manual (transfer BCA / QRIS statis), dikonfirmasi admin
| lewat /admin/pesanan. Booking pickup ke Biteship SENGAJA TIDAK otomatis
| begitu lunas — admin klik sendiri "Atur Pengiriman Sekarang" waktu barang
| sudah siap kirim (lihat OrderService::arrangeShipment). "Beli di
| Shopee/Tokopedia" tetap ada di halaman produk sebagai opsi kedua, tidak
| dihapus. Integrasi Midtrans (route bayar-ulang & webhook) tidak dihapus,
| cuma tidak dipakai sekarang.
*/
Route::get('/keranjang', function () {
    view()->share('meta', [
        'title' => 'Keranjang — '.config('app.name', 'NALE'),
        'description' => 'Isi keranjang belanjamu sebelum checkout.',
    ]);

    return Inertia::render('Cart');
})->name('cart');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout/ongkir', [CheckoutController::class, 'rates'])->name('checkout.rates');
// throttle: kirim-otp beneran nembak biaya WhatsApp (Fonnte) tiap panggil, dan
// verifikasi-otp/diskon rawan ditebak-tebak brute-force kalau tanpa batas.
Route::post('/checkout/kirim-otp', [CheckoutController::class, 'sendOtp'])->name('checkout.send-otp')->middleware('throttle:3,1');
Route::post('/checkout/verifikasi-otp', [CheckoutController::class, 'verifyOtp'])->name('checkout.verify-otp')->middleware('throttle:10,1');
Route::post('/checkout/diskon', [CheckoutController::class, 'discount'])->name('checkout.discount')->middleware('throttle:10,1');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
Route::patch('/checkout/{order:tracking_token}/bayar-ulang', [CheckoutController::class, 'repay'])->name('checkout.repay');
Route::get('/pesanan/{order:tracking_token}', [CheckoutController::class, 'track'])->name('order.track');
Route::post('/pesanan/{order:tracking_token}/bukti-bayar', [CheckoutController::class, 'uploadProof'])->name('order.upload-proof');
// throttle: cegah spam submit ulasan berulang-ulang (unique order_id sudah jadi guard utama).
Route::post('/pesanan/{order:tracking_token}/ulasan', [ReviewController::class, 'store'])->name('review.store')->middleware('throttle:5,1');

// Buyer lupa/kehilangan link tracking-nya bisa cari lagi di sini pakai No. WhatsApp +
// OTP (reuse endpoint kirim-otp/verifikasi-otp di atas, sama persis kayak di Checkout).
Route::get('/pesanan-saya', function () {
    view()->share('meta', [
        'title' => 'Cek Pesanan Saya — '.config('app.name', 'NALE'),
        'description' => 'Lupa link tracking pesananmu? Cari lagi lewat No. WhatsApp.',
    ]);

    return Inertia::render('Order/Lookup');
})->name('order.lookup');

Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans'])->name('webhooks.midtrans');
Route::post('/webhooks/biteship/{token}', [WebhookController::class, 'biteship'])->name('webhooks.biteship');

/*
|--- Admin ---------------------------------------------------------------
| Login sederhana (password di .env: ADMIN_PASSWORD).
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    // throttle: batasi percobaan password biar gak bisa brute-force — sekarang uang
    // beneran lewat panel ini, bukan cuma katalog.
    Route::post('login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/', [ProductAdminController::class, 'index'])->name('products');
        Route::post('produk', [ProductAdminController::class, 'store'])->name('products.store');
        Route::put('produk/{product}', [ProductAdminController::class, 'update'])->name('products.update');
        Route::delete('produk/{product}', [ProductAdminController::class, 'destroy'])->name('products.destroy');
        Route::post('upload', [ProductAdminController::class, 'upload'])->name('upload');

        Route::get('pesanan', [OrderAdminController::class, 'index'])->name('orders');
        Route::get('pesanan/{order:id}/tracking', [OrderAdminController::class, 'tracking'])->name('orders.tracking');
        Route::put('pesanan/{order:id}', [OrderAdminController::class, 'updateOrder'])->name('orders.update');
        Route::patch('pesanan/{order:id}/atur-pengiriman', [OrderAdminController::class, 'arrangeShipment'])->name('orders.arrange-shipment');
        Route::patch('pesanan/{order:id}/konfirmasi-bayar', [OrderAdminController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::patch('pesanan/{order:id}/batalkan', [OrderAdminController::class, 'cancel'])->name('orders.cancel');
        Route::post('diskon', [OrderAdminController::class, 'storeDiscount'])->name('discounts.store');
        Route::put('diskon/{discount}', [OrderAdminController::class, 'updateDiscount'])->name('discounts.update');
        Route::patch('diskon/{discount}/toggle', [OrderAdminController::class, 'toggleDiscount'])->name('discounts.toggle');
        Route::delete('diskon/{discount}', [OrderAdminController::class, 'destroyDiscount'])->name('discounts.destroy');
        Route::post('qris', [OrderAdminController::class, 'uploadQris'])->name('qris.upload');

        Route::get('ulasan', [ReviewController::class, 'index'])->name('reviews');
        Route::patch('ulasan/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::patch('ulasan/{review}/tampilkan', [ReviewController::class, 'toggleFeatured'])->name('reviews.toggle-featured');
        Route::delete('ulasan/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    });
});
