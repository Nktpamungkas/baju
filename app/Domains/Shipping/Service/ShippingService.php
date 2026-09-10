<?php

namespace App\Domains\Shipping\Service;

use App\Domains\Shipping\Repository\ShippingRateRepository;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    private const BASE_URL = 'https://api.biteship.com/v1';

    public function __construct(private ShippingRateRepository $quotes)
    {
    }

    // $items: array<{name, price, weight, qty}>. Di-cache per titik lokasi (radius ~1km)
    // + berat barang — Biteship charge tiap panggilan API-nya, jadi lokasi yang sudah
    // pernah dicek (oleh buyer manapun) tidak perlu nembak API lagi selama masih valid.
    public function rates(float $destinationLat, float $destinationLng, array $items): array
    {
        $weight = $this->weightBucket($items);

        if ($cached = $this->quotes->findNearby($destinationLat, $destinationLng, $weight)) {
            return $cached->options;
        }

        $payload = [
            // origin_area_id SAJA bikin kurir on-demand (Gojek/Grab) tidak pernah muncul —
            // kurir jenis itu wajib titik koordinat presisi buat origin, bukan cuma area_id.
            'origin_latitude'       => (float) config('services.biteship.origin_lat'),
            'origin_longitude'      => (float) config('services.biteship.origin_lng'),
            'destination_latitude'  => $destinationLat,
            'destination_longitude' => $destinationLng,
            'couriers'              => config('services.biteship.couriers'),
            'items'                 => collect($items)->map(fn (array $i) => [
                'name'     => $i['name'],
                'value'    => $i['price'],
                'weight'   => $i['weight'],
                'quantity' => $i['qty'],
            ])->values()->all(),
        ];

        $pricing = $this->client()->post('/rates/couriers', $payload)->throw()->json('pricing', []);

        $options = collect($pricing)
            ->map(fn (array $p) => [
                'courier_code'    => $p['courier_code'],
                'courier_service' => $p['courier_service_code'],
                'label'           => trim(($p['courier_name'] ?? '').' '.($p['courier_service_name'] ?? '')),
                'price'           => (int) $p['price'],
                'duration'        => $p['duration'] ?? null,
            ])
            ->sortBy('price')
            ->values()
            ->all();

        $this->quotes->store($destinationLat, $destinationLng, $weight, $options);

        return $options;
    }

    // Bulatkan ke atas per 500g biar variasi kecil berat produk tetap bisa saling pakai cache.
    private function weightBucket(array $items): int
    {
        $total = collect($items)->sum(fn (array $i) => $i['weight'] * $i['qty']);

        return (int) (ceil(max($total, 1) / 500) * 500);
    }

    public function bookPickup(Order $order): array
    {
        $address = $order->shipping_address ?? [];
        $option = $order->shipping_option ?? [];

        $payload = [
            'origin_contact_name'  => config('services.biteship.origin_contact_name'),
            'origin_contact_phone' => config('services.biteship.origin_contact_phone'),
            'origin_address'       => config('services.biteship.origin_address'),
            'origin_coordinate'    => [
                'latitude'  => (float) config('services.biteship.origin_lat'),
                'longitude' => (float) config('services.biteship.origin_lng'),
            ],

            'destination_contact_name'   => $order->customer_name,
            'destination_contact_phone'  => $order->phone,
            'destination_address'        => $address['detail'] ?? '',
            // API order/booking (beda dari API rates) butuh koordinat sebagai nested object.
            'destination_coordinate'     => [
                'latitude'  => $address['lat'] ?? null,
                'longitude' => $address['lng'] ?? null,
            ],

            'courier_company' => $option['courier_code'] ?? null,
            'courier_type'    => $option['courier_service'] ?? null,
            'delivery_type'   => 'now',

            'items' => $order->items->map(fn ($item) => [
                'name'     => $item->name,
                'value'    => $item->price,
                'weight'   => $item->weight ?? 200,
                'quantity' => $item->qty,
            ])->values()->all(),
        ];

        $res = $this->client()->post('/orders', $payload)->throw()->json();

        return [
            'biteship_order_id' => $res['id'] ?? null,
            'tracking_id'       => $res['courier']['tracking_id'] ?? null,
            'waybill_id'        => $res['courier']['waybill_id'] ?? null,
            'status'            => $res['status'] ?? null,
        ];
    }

    public function track(string $biteshipOrderId): array
    {
        return $this->client()->get("/trackings/{$biteshipOrderId}")->throw()->json();
    }

    public function cancelOrder(string $biteshipOrderId): array
    {
        return $this->client()
            ->post("/orders/{$biteshipOrderId}/cancel", ['cancellation_reason_code' => 'others'])
            ->throw()
            ->json();
    }

    // Biteship tidak sign payload webhook-nya — verifikasi asal request dilakukan
    // di WebhookController lewat token acak di path URL, bukan di sini.
    public function parseWebhook(array $payload): array
    {
        return [
            'biteship_order_id' => $payload['order_id'] ?? null,
            'status'            => $payload['status'] ?? null,
            'waybill_id'        => $payload['courier_waybill_id'] ?? null,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders(['Authorization' => config('services.biteship.api_key')])
            ->baseUrl(self::BASE_URL);
    }
}
