<?php

namespace App\Domains\Payment\Service;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function createTransaction(Order $order): array
    {
        $midtransOrderId = "NALE-{$order->id}-".time();

        $payload = [
            'transaction_details' => [
                'order_id'     => $midtransOrderId,
                'gross_amount' => $order->total,
            ],
            'item_details' => $order->items->map(fn ($i) => [
                'id'       => (string) $i->id,
                'price'    => $i->price,
                'quantity' => $i->qty,
                'name'     => Str::limit($i->name, 50, ''),
            ])->values()->all(),
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email'      => $order->email,
                'phone'      => $order->phone,
            ],
        ];

        $res = Http::withBasicAuth(config('services.midtrans.server_key'), '')
            ->post($this->baseUrl().'/snap/v1/transactions', $payload)
            ->throw()
            ->json();

        return [
            'midtrans_order_id' => $midtransOrderId,
            'snap_token'        => $res['token'],
            'redirect_url'      => $res['redirect_url'],
        ];
    }

    // Verifikasi signature + normalisasi payload. Lempar exception kalau signature
    // tidak cocok — WebhookController yang menentukan respons HTTP-nya.
    public function parseNotification(array $payload): array
    {
        if (! $this->verifySignature($payload)) {
            throw new RuntimeException('Invalid Midtrans signature');
        }

        return [
            'midtrans_order_id' => $payload['order_id'] ?? null,
            'status'            => $payload['transaction_status'] ?? null,
            'fraud_status'      => $payload['fraud_status'] ?? null,
            'payment_method'    => $payload['payment_type'] ?? null,
        ];
    }

    private function verifySignature(array $payload): bool
    {
        $expected = hash('sha512',
            ($payload['order_id'] ?? '').
            ($payload['status_code'] ?? '').
            ($payload['gross_amount'] ?? '').
            config('services.midtrans.server_key')
        );

        return hash_equals($expected, $payload['signature_key'] ?? '');
    }

    private function baseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }
}
