<?php

namespace App\Domains\Order\Controller;

use App\Domains\Order\Service\OrderService;
use App\Models\Discount;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderAdminController
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Orders', [
            'orders'    => $this->orders->listOrders(),
            'discounts' => $this->orders->listDiscounts(),
        ]);
    }

    // Dipanggil lazy per baris pesanan waktu admin klik "Cek Update Kurir" —
    // sengaja tidak eager-load buat semua pesanan sekaligus di index().
    public function tracking(Order $order): JsonResponse
    {
        return response()->json(['carrier' => $this->orders->trackingData($order)['carrier']]);
    }

    public function arrangeShipment(Order $order): RedirectResponse
    {
        try {
            $this->orders->arrangeShipment($order);
        } catch (RuntimeException $e) {
            return back()->withErrors(['pickup' => $e->getMessage()]);
        }

        return back();
    }

    public function confirmPayment(Order $order): RedirectResponse
    {
        $this->orders->confirmPayment($order);

        return back();
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'customer_name'          => 'required|string|max:120',
            'phone'                  => 'required|string|max:40',
            'email'                  => 'nullable|email|max:150',
            'shipping_address'       => 'required|array',
            'shipping_address.detail' => 'required|string|max:500',
            'shipping_address.label'  => 'nullable|string|max:255',
            'shipping_address.lat'    => 'required|numeric',
            'shipping_address.lng'    => 'required|numeric',
        ]);

        try {
            $this->orders->updateOrder($order, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['edit' => $e->getMessage()]);
        }

        return back();
    }

    public function cancel(Order $order): RedirectResponse
    {
        try {
            $this->orders->cancelOrder($order);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back();
    }

    // QRIS statis: selalu ditimpa ke nama file yang sama, tidak perlu tabel/kolom baru.
    public function uploadQris(Request $request): RedirectResponse
    {
        $request->validate(['qris' => 'required|image|max:5120']);
        $request->file('qris')->move(public_path('img'), 'qris.png');

        return back();
    }

    public function storeDiscount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'         => 'required|string|max:40|unique:discounts,code',
            'type'         => 'required|in:percent,fixed',
            'value'        => 'required|integer|min:1',
            'min_subtotal' => 'nullable|integer|min:0',
            'expires_at'   => 'nullable|date',
            'featured'     => 'nullable|boolean',
        ]);

        $this->orders->createDiscount($data);

        return back();
    }

    public function updateDiscount(Request $request, Discount $discount): RedirectResponse
    {
        $data = $request->validate([
            'code'         => 'required|string|max:40|unique:discounts,code,'.$discount->id,
            'type'         => 'required|in:percent,fixed',
            'value'        => 'required|integer|min:1',
            'min_subtotal' => 'nullable|integer|min:0',
            'expires_at'   => 'nullable|date',
            'featured'     => 'nullable|boolean',
        ]);

        $this->orders->updateDiscount($discount, $data);

        return back();
    }

    public function toggleDiscount(Discount $discount): RedirectResponse
    {
        $this->orders->toggleDiscount($discount);

        return back();
    }

    public function destroyDiscount(Discount $discount): RedirectResponse
    {
        $this->orders->deleteDiscount($discount);

        return back();
    }
}
