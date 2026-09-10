<?php

namespace App\Domains\Order\Controller;

use App\Domains\Order\Service\OrderService;
use App\Domains\Payment\Service\PaymentService;
use App\Domains\Shipping\Service\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WebhookController
{
    public function __construct(
        private OrderService $orders,
        private PaymentService $payment,
        private ShippingService $shipping,
    ) {
    }

    public function midtrans(Request $request): JsonResponse
    {
        try {
            $data = $this->payment->parseNotification($request->all());
        } catch (RuntimeException $e) {
            Log::warning('midtrans.invalid_signature', $request->all());

            return response()->json(['status' => 'invalid signature'], 403);
        }

        $this->orders->applyPaymentStatus($data);

        return response()->json(['status' => 'ok']);
    }

    public function biteship(Request $request, string $token): JsonResponse
    {
        if (! hash_equals((string) config('services.biteship.webhook_token'), $token)) {
            return response()->json(['status' => 'invalid token'], 403);
        }

        $this->orders->applyShippingStatus($this->shipping->parseWebhook($request->all()));

        return response()->json(['status' => 'ok']);
    }
}
