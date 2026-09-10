<?php

namespace App\Domains\Order\Controller;

use App\Domains\Order\Service\OrderService;
use App\Domains\Review\Service\ReviewService;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;

class CheckoutController
{
    public function __construct(
        private OrderService $orders,
        private ReviewService $reviews,
    ) {
    }

    public function show(): Response
    {
        view()->share('meta', [
            'title' => 'Checkout — '.config('app.name', 'NALE'),
            'description' => 'Selesaikan pesananmu — alamat, ongkir, dan pembayaran.',
        ]);

        return Inertia::render('Checkout', [
            'qrisAvailable' => file_exists(public_path('img/qris.png')),
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat'                 => 'required|numeric|between:-90,90',
            'lng'                 => 'required|numeric|between:-180,180',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|string',
            'items.*.qty'         => 'required|integer|min:1',
        ]);

        try {
            $options = $this->orders->rates((float) $data['lat'], (float) $data['lng'], $data['items']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['options' => $options]);
    }

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(
            ['phone' => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{8,12}$/']],
            ['phone.regex' => 'Format No. WhatsApp tidak valid, contoh: 08123456789']
        );

        try {
            $this->orders->sendOtp($data['phone']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'sent']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => 'required|string|min:8|max:20',
            'code'  => 'required|string|size:6',
        ]);

        try {
            $result = $this->orders->verifyOtp($data['phone'], $data['code']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function discount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|integer|min:0',
        ]);

        try {
            $result = $this->orders->previewDiscount($data['code'], $data['subtotal']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name'      => 'required|string|max:120',
            'phone'              => ['required', 'string', 'regex:/^(\+?62|0)8[0-9]{8,12}$/'],
            'email'              => 'nullable|email|max:150',
            'shipping_address'   => 'required|array',
            'shipping_option'    => 'required|array',
            'shipping_cost'      => 'required|integer|min:0',
            'discount_code'      => 'nullable|string',
            'payment_method'     => 'required|in:bank_transfer,qris',
            'save_profile'       => 'nullable|boolean',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.variant'    => 'nullable|string',
            'items.*.size'       => 'nullable|string',
            'items.*.qty'        => 'required|integer|min:1',
        ], [
            'phone.regex' => 'Format No. WhatsApp tidak valid, contoh: 08123456789',
        ]);

        try {
            $result = $this->orders->checkout($data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function repay(Order $order): JsonResponse
    {
        try {
            $result = $this->orders->repay($order);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function track(Order $order): Response
    {
        view()->share('meta', [
            'title' => 'Lacak Pesanan #'.$order->id.' — '.config('app.name', 'NALE'),
            'description' => 'Cek status pembayaran dan pengiriman pesananmu.',
        ]);

        return Inertia::render('Order/Track', [
            ...$this->orders->trackingData($order),
            'review' => $this->reviews->findByOrderId($order->id),
        ]);
    }

    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['photo' => 'required|image|max:20480']);

        $this->orders->uploadProof($order, $request->file('photo'));

        return back();
    }
}
