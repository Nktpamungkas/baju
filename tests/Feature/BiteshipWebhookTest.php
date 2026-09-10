<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BiteshipWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        return Order::create([
            'tracking_token' => 'tok-'.uniqid(),
            'customer_name' => 'Budi',
            'phone' => '081234567890',
            'shipping_address' => ['detail' => 'Jl. Test', 'lat' => -6.2, 'lng' => 106.8],
            'subtotal' => 100000,
            'total' => 110000,
            'payment_status' => 'settlement',
            'fulfillment_status' => 'booked',
            'biteship_order_id' => 'bts-123',
        ]);
    }

    public function test_wrong_token_is_rejected_and_order_is_untouched(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/webhooks/biteship/token-yang-salah', [
            'order_id' => 'bts-123',
            'status' => 'delivered',
        ])->assertStatus(403);

        $this->assertSame('booked', $order->fresh()->fulfillment_status);
    }

    public function test_correct_token_updates_fulfillment_status_and_waybill(): void
    {
        $order = $this->makeOrder();
        $token = config('services.biteship.webhook_token');

        $this->postJson("/webhooks/biteship/{$token}", [
            'order_id' => 'bts-123',
            'status' => 'delivered',
            'courier_waybill_id' => 'WB-999',
        ])->assertOk();

        $order->refresh();
        $this->assertSame('delivered', $order->fulfillment_status);
        $this->assertSame('WB-999', $order->waybill_id);
    }

    public function test_webhook_for_unknown_biteship_order_is_ignored_without_error(): void
    {
        $token = config('services.biteship.webhook_token');

        $this->postJson("/webhooks/biteship/{$token}", [
            'order_id' => 'tidak-dikenal',
            'status' => 'delivered',
        ])->assertOk();
    }
}
