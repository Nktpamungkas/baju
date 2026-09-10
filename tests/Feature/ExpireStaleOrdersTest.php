<?php

namespace Tests\Feature;

use App\Domains\Order\Service\OrderService;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpireStaleOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaleOrder(string $paymentStatus): Order
    {
        Product::updateOrCreate(['id' => 'baju-expire'], [
            'name' => 'Baju Expire', 'type' => 'Setelan', 'price' => 50000, 'weight' => 200, 'stock' => 2,
            'variants' => [], 'sizeCols' => [], 'sizes' => [],
        ]);

        $order = Order::create([
            'tracking_token' => 'tok-'.uniqid(),
            'customer_name' => 'Budi', 'phone' => '081234567890',
            'shipping_address' => ['detail' => 'x', 'lat' => -6, 'lng' => 106],
            'subtotal' => 50000, 'total' => 50000,
            'payment_status' => $paymentStatus, 'fulfillment_status' => 'unbooked',
            'created_at' => now()->subHours(30),
        ]);
        $order->items()->create(['product_id' => 'baju-expire', 'name' => 'Baju Expire', 'price' => 50000, 'weight' => 200, 'qty' => 1]);
        Product::whereKey('baju-expire')->decrement('stock', 1);

        return $order;
    }

    public function test_stale_pending_order_is_expired_and_stock_is_returned(): void
    {
        $order = $this->makeStaleOrder('pending');

        $count = app(OrderService::class)->expireStaleOrders(24);

        $this->assertSame(1, $count);
        $this->assertSame('expire', $order->fresh()->payment_status);
        $this->assertSame(2, Product::find('baju-expire')->stock);
    }

    public function test_recent_pending_order_is_not_expired(): void
    {
        $order = $this->makeStaleOrder('pending');
        $order->update(['created_at' => now()->subHours(1)]);

        $count = app(OrderService::class)->expireStaleOrders(24);

        $this->assertSame(0, $count);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(1, Product::find('baju-expire')->stock);
    }

    public function test_already_settled_order_is_never_expired(): void
    {
        $order = $this->makeStaleOrder('settlement');

        $count = app(OrderService::class)->expireStaleOrders(24);

        $this->assertSame(0, $count);
        $this->assertSame('settlement', $order->fresh()->payment_status);
    }

    public function test_cancelling_an_already_expired_order_does_not_double_refund_stock(): void
    {
        $order = $this->makeStaleOrder('pending');
        app(OrderService::class)->expireStaleOrders(24);
        $this->assertSame(2, Product::find('baju-expire')->stock);

        app(OrderService::class)->cancelOrder($order->fresh());

        $this->assertSame(2, Product::find('baju-expire')->stock);
        $this->assertSame('expire', $order->fresh()->payment_status);
    }
}
