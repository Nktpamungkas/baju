<?php

namespace App\Domains\Order\Service;

use App\Domains\Notification\Service\WhatsappService;
use App\Domains\Order\Repository\CustomerRepository;
use App\Domains\Order\Repository\DiscountRepository;
use App\Domains\Order\Repository\OrderRepository;
use App\Domains\Order\Repository\OtpRepository;
use App\Domains\Payment\Service\PaymentService;
use App\Domains\Product\Repository\ProductRepository;
use App\Domains\Product\Service\ProductService;
use App\Domains\Shipping\Service\ShippingService;
use App\Models\Discount;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OrderService
{
    public function __construct(
        private OrderRepository $orders,
        private DiscountRepository $discounts,
        private ProductRepository $products,
        private ProductService $productService,
        private PaymentService $payment,
        private ShippingService $shipping,
        private CustomerRepository $customers,
        private OtpRepository $otp,
        private WhatsappService $whatsapp,
    ) {
    }

    public function sendOtp(string $phone): void
    {
        $code = $this->otp->generate($phone);

        try {
            $sent = $this->whatsapp->send($phone, "Kode verifikasi NALE kamu: {$code} (berlaku 5 menit, jangan kasih ke siapapun).");
        } catch (Throwable $e) {
            Log::error('whatsapp.send_otp_failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            throw new RuntimeException('Gagal mengirim kode verifikasi, coba lagi nanti.');
        }

        if (! $sent) {
            throw new RuntimeException('Gagal mengirim kode verifikasi, coba lagi nanti.');
        }
    }

    // Return: {profile, has_delivered_order, orders} — dipakai baik oleh verifikasi
    // nomor di Checkout.vue (auto-isi form) maupun halaman "Cek Pesanan" (lihat semua
    // pesanan lama). Satu method OTP-gated ini yang jadi sumber buat keduanya.
    public function verifyOtp(string $phone, string $code): array
    {
        if (! $this->otp->verify($phone, $code)) {
            throw new InvalidArgumentException('Kode OTP salah atau sudah kedaluwarsa.');
        }

        $customer = $this->customers->findByPhone($phone);
        $orders = $this->orders->findAllByPhone($phone);

        return [
            'profile' => $customer ? [
                'name'    => $customer->name,
                'email'   => $customer->email,
                'address' => $customer->address,
            ] : null,
            'has_delivered_order' => $orders->contains(fn (Order $o) => $o->fulfillment_status === 'delivered'),
            'orders' => $orders->map(fn (Order $o) => [
                'id'                 => $o->id,
                'tracking_token'     => $o->tracking_token,
                'created_at'         => $o->created_at,
                'total'              => $o->total,
                'payment_status'     => $o->payment_status,
                'fulfillment_status' => $o->fulfillment_status,
            ])->values()->all(),
        ];
    }

    // items: array<{product_id, variant?, size?, qty}>
    public function rates(float $lat, float $lng, array $items): array
    {
        return $this->shipping->rates($lat, $lng, $this->resolveItems($items));
    }

    public function previewDiscount(?string $code, int $subtotal): array
    {
        return $this->resolveDiscount($code, $subtotal);
    }

    // Dipakai buat banner promo di beranda (lihat ProductController::home).
    public function featuredDiscount(): ?Discount
    {
        return $this->discounts->activeFeatured();
    }

    // Pembayaran aktif saat ini: manual (transfer bank / QRIS statis), dikonfirmasi admin
    // lewat confirmPayment(). Integrasi Midtrans (createTransaction/repay/applyPaymentStatus
    // di bawah) sengaja TIDAK dihapus — tinggal disambung lagi ke sini kalau nanti mau
    // pakai payment gateway otomatis (mis. sudah punya usaha terdaftar).
    public function checkout(array $data): array
    {
        $items = $this->resolveItems($data['items']);
        $subtotal = collect($items)->sum(fn (array $i) => $i['price'] * $i['qty']);
        $discount = $this->resolveDiscount($data['discount_code'] ?? null, $subtotal);
        $shippingCost = max(0, (int) ($data['shipping_cost'] ?? 0));
        $total = max(0, $subtotal - $discount['amount']) + $shippingCost;

        $order = DB::transaction(function () use ($data, $items, $subtotal, $discount, $shippingCost, $total) {
            // Stok dikurangi di sini (bukan pas admin konfirmasi bayar) — kalau kurang,
            // seluruh transaksi (termasuk item lain di order yang sama) ikut batal.
            foreach ($items as $item) {
                if (! $this->products->decrementStock($item['product_id'], $item['qty'])) {
                    throw new InvalidArgumentException("Stok \"{$item['name']}\" tidak cukup.");
                }
            }

            $order = $this->orders->create([
                'tracking_token'    => Str::random(40),
                'customer_name'     => $data['customer_name'],
                'phone'             => $data['phone'],
                'email'             => $data['email'] ?? null,
                'shipping_address'  => $data['shipping_address'],
                'shipping_option'   => $data['shipping_option'] ?? null,
                'shipping_cost'     => $shippingCost,
                'subtotal'          => $subtotal,
                'discount_code'     => $discount['code'],
                'discount_amount'   => $discount['amount'],
                'total'             => $total,
                'payment_method'    => $data['payment_method'],
            ]);

            foreach ($items as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        if (! empty($data['save_profile'])) {
            $this->saveProfileIfVerified($data);
        }

        $this->notifyAdmin("🛍️ Pesanan baru #{$order->id} dari {$order->customer_name} ({$order->phone}) — Rp".number_format($order->total, 0, ',', '.'));

        return ['tracking_url' => route('order.track', $order->tracking_token)];
    }

    // Simpan profil HANYA kalau nomor ini beneran baru diverifikasi OTP — jangan
    // percaya begitu saja flag save_profile dari client tanpa bukti verifikasi.
    private function saveProfileIfVerified(array $data): void
    {
        if (! $this->otp->isRecentlyVerified($data['phone'])) {
            return;
        }

        try {
            $this->customers->upsert($data['phone'], [
                'name'    => $data['customer_name'],
                'email'   => $data['email'] ?? null,
                'address' => $data['shipping_address'],
            ]);
        } catch (Throwable $e) {
            Log::warning('customer.save_profile_failed', ['phone' => $data['phone'], 'error' => $e->getMessage()]);
        }
    }

    // Buyer upload bukti transfer/screenshot QRIS dari halaman tracking.
    public function uploadProof(Order $order, UploadedFile $file): void
    {
        $path = $this->productService->storeUploadedPhoto($file);

        $this->orders->update($order, [
            'payment_proof'  => $path,
            'payment_status' => $order->payment_status === 'pending' ? 'menunggu_konfirmasi' : $order->payment_status,
        ]);

        $this->notifyAdmin("📄 Bukti bayar diupload untuk pesanan #{$order->id} ({$order->customer_name}) — cek di /admin/pesanan.");
    }

    // Admin klik "Tandai Lunas" setelah cek mutasi/bukti transfer secara manual.
    // Booking pickup ke Biteship SENGAJA TIDAK otomatis di sini — admin yang trigger
    // sendiri lewat arrangeShipment() begitu barangnya beneran siap dikirim (lihat method itu).
    public function confirmPayment(Order $order): void
    {
        if ($order->payment_status === 'settlement') {
            return;
        }

        $this->orders->update($order, [
            'payment_status' => 'settlement',
            'paid_at'        => now(),
        ]);
    }

    public function repay(Order $order): array
    {
        if ($order->payment_status === 'settlement') {
            throw new RuntimeException('Order sudah dibayar.');
        }

        try {
            $tx = $this->payment->createTransaction($order->load('items'));
        } catch (Throwable $e) {
            Log::error('midtrans.create_transaction_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            throw new RuntimeException('Gagal menghubungi payment gateway, coba lagi nanti.');
        }

        $this->orders->update($order, ['midtrans_order_id' => $tx['midtrans_order_id']]);

        return ['snap_token' => $tx['snap_token']];
    }

    // $data dari PaymentService::parseNotification(): {midtrans_order_id, status, fraud_status, payment_method}
    public function applyPaymentStatus(array $data): void
    {
        $order = $this->orders->findByMidtransOrderId($data['midtrans_order_id'] ?? '');

        if (! $order || $order->payment_status === 'settlement') {
            return; // tidak ditemukan, atau sudah lunas — idempotent terhadap notifikasi dobel
        }

        $status = $data['status'] ?? null;
        $paid = $status === 'settlement' || ($status === 'capture' && ($data['fraud_status'] ?? null) === 'accept');

        if ($paid) {
            $this->orders->update($order, [
                'payment_status' => 'settlement',
                'payment_method' => $data['payment_method'] ?? null,
                'paid_at'        => now(),
            ]);

            return;
        }

        $this->orders->update($order, ['payment_status' => $status]);
    }

    // $data dari ShippingService::parseWebhook(): {biteship_order_id, status, waybill_id}
    // Biteship kirim webhook terpisah per jenis event (status saja, atau waybill saja) —
    // jangan timpa satu field jadi null gara-gara event lain yang lagi update field sebelahnya.
    public function applyShippingStatus(array $data): void
    {
        if (empty($data['biteship_order_id'])) {
            return;
        }

        $order = $this->orders->findByBiteshipOrderId($data['biteship_order_id']);

        if (! $order) {
            return;
        }

        $update = [];
        if (! empty($data['status'])) {
            $update['fulfillment_status'] = $data['status'];
        }
        if (! empty($data['waybill_id'])) {
            $update['waybill_id'] = $data['waybill_id'];
        }

        if ($update) {
            $this->orders->update($order, $update);
        }
    }

    // Admin benerin typo nama/telp/email/alamat — SENGAJA dibatasi cuma field ini,
    // gak boleh nyentuh item/harga/ongkir (itu snapshot uang, sudah dikunci sejak
    // checkout). Kalau tujuan berubah total & booking belum jalan, ongkir yang sudah
    // dibayar tetap dipakai (bukan dihitung ulang) — admin yang nilai wajar/tidaknya.
    public function updateOrder(Order $order, array $data): void
    {
        if ($order->fulfillment_status !== 'unbooked') {
            throw new RuntimeException('Pesanan sudah dibooking ke kurir, perubahan alamat di sini tidak akan sampai ke kurir. Batalkan & buat baru kalau alamatnya beneran salah.');
        }

        $this->orders->update($order, array_filter([
            'customer_name'    => $data['customer_name'] ?? null,
            'phone'            => $data['phone'] ?? null,
            'email'            => $data['email'] ?? null,
            'shipping_address' => $data['shipping_address'] ?? null,
        ], fn ($v) => $v !== null));
    }

    // Admin klik "Atur Pengiriman Sekarang" (atau "Coba Lagi" kalau attempt sebelumnya
    // gagal) — satu-satunya jalan booking pickup ke Biteship benar-benar terjadi.
    public function arrangeShipment(Order $order): void
    {
        if ($order->payment_status !== 'settlement' || $order->fulfillment_status !== 'unbooked') {
            throw new RuntimeException('Order belum lunas atau sudah dibooking.');
        }

        $this->bookPickup($order);
    }

    // Kalau sudah kebooking pickup di Biteship, batalkan juga di sana dulu — kalau paket
    // ternyata sudah diambil kurir, Biteship akan menolak & pesanan TIDAK ditandai batal
    // di sistem kita (biar tidak keliru dianggap batal padahal barang sudah jalan).
    public function cancelOrder(Order $order): void
    {
        // 'expire' juga sudah mengembalikan stok (lihat expireStaleOrders) — kalau tidak
        // di-skip di sini, klik "Batalkan" di order yang sudah expire bakal nambah stok DOBEL.
        if (in_array($order->payment_status, ['cancel', 'expire'], true)) {
            return;
        }

        $order->load('items');

        if ($order->biteship_order_id) {
            try {
                $this->shipping->cancelOrder($order->biteship_order_id);
            } catch (Throwable $e) {
                Log::error('biteship.cancel_order_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                throw new RuntimeException('Gagal membatalkan pengiriman di Biteship — mungkin paket sudah diambil kurir. Cek langsung ke Biteship/kurir kalau perlu.');
            }
        }

        foreach ($order->items as $item) {
            if ($item->product_id) {
                $this->products->incrementStock($item->product_id, $item->qty);
            }
        }

        $this->orders->update($order, [
            'payment_status'     => 'cancel',
            'fulfillment_status' => 'cancelled',
        ]);
    }

    // Pesanan yang gak dibayar-bayar (belum upload bukti / belum dikonfirmasi admin)
    // sampai batas waktu tertentu di-expire otomatis + stoknya dikembalikan — biar stok
    // gak "ketahan" selamanya sama keranjang yang ditinggal. Dipanggil dari
    // Console/Commands/ExpireStaleOrders lewat scheduler (lihat routes/console.php).
    public function expireStaleOrders(int $hours = 24): int
    {
        $orders = $this->orders->findStalePending($hours);

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $this->products->incrementStock($item->product_id, $item->qty);
                }
            }

            $this->orders->update($order, ['payment_status' => 'expire']);
        }

        return $orders->count();
    }

    public function trackingData(Order $order): array
    {
        $order->load('items');
        $carrier = null;

        if ($order->biteship_order_id) {
            try {
                $carrier = $this->shipping->track($order->biteship_order_id);
            } catch (Throwable) {
                $carrier = null;
            }
        }

        return ['order' => $order, 'carrier' => $carrier];
    }

    public function listOrders(): Collection
    {
        return $this->orders->allWithItems();
    }

    public function listDiscounts(): Collection
    {
        return $this->discounts->all();
    }

    public function createDiscount(array $data): Discount
    {
        return $this->discounts->create($data);
    }

    public function updateDiscount(Discount $discount, array $data): void
    {
        $this->discounts->update($discount, $data);
    }

    public function toggleDiscount(Discount $discount): void
    {
        $this->discounts->update($discount, ['active' => ! $discount->active]);
    }

    public function deleteDiscount(Discount $discount): void
    {
        $this->discounts->delete($discount);
    }

    private function bookPickup(Order $order): void
    {
        try {
            $booking = $this->shipping->bookPickup($order->load('items'));
            $this->orders->update($order, array_filter([
                'biteship_order_id'  => $booking['biteship_order_id'],
                'fulfillment_status' => 'booked',
                'waybill_id'         => $booking['waybill_id'] ?? null,
            ]));
        } catch (Throwable $e) {
            Log::error('biteship.book_pickup_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    // Notifikasi ke WhatsApp pemilik toko (nomor yang sama dengan tombol "Tanya via
    // WhatsApp" di footer) — gagal kirim tidak boleh menggagalkan checkout/upload bukti.
    private function notifyAdmin(string $message): void
    {
        $adminPhone = config('nale.whatsapp');

        if (! $adminPhone) {
            return;
        }

        try {
            $this->whatsapp->send($adminPhone, $message);
        } catch (Throwable $e) {
            Log::warning('whatsapp.notify_admin_failed', ['error' => $e->getMessage()]);
        }
    }

    // Harga/berat TIDAK PERNAH dipercaya dari client — selalu diambil ulang dari Product.
    private function resolveItems(array $lines): array
    {
        return collect($lines)->map(function (array $line) {
            $productId = $line['product_id'] ?? $line['id'] ?? null;
            $product = $productId ? $this->products->find($productId) : null;

            if (! $product) {
                throw new InvalidArgumentException('Produk tidak ditemukan: '.($productId ?? '?'));
            }

            return [
                'product_id' => $product->id,
                'name'       => $product->name,
                'variant'    => $line['variant'] ?? null,
                'size'       => $line['size'] ?? null,
                'price'      => $product->price,
                'weight'     => $product->weight,
                'qty'        => max(1, (int) ($line['qty'] ?? 1)),
            ];
        })->all();
    }

    private function resolveDiscount(?string $code, int $subtotal): array
    {
        if (! $code) {
            return ['code' => null, 'amount' => 0];
        }

        $discount = $this->discounts->findActiveByCode($code);

        if (! $discount || ($discount->expires_at && $discount->expires_at->isPast()) || $subtotal < $discount->min_subtotal) {
            throw new InvalidArgumentException('Kode diskon tidak valid.');
        }

        $amount = $discount->type === 'percent'
            ? intdiv($subtotal * $discount->value, 100)
            : $discount->value;

        return ['code' => $discount->code, 'amount' => min($amount, $subtotal)];
    }
}
