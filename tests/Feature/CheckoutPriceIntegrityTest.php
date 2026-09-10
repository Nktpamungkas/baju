<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutPriceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'id' => 'baju-test', 'name' => 'Baju Test', 'type' => 'Setelan',
            'price' => 100000, 'weight' => 200, 'stock' => null,
            'variants' => [], 'sizeCols' => [], 'sizes' => [],
        ], $overrides));
    }

    private function checkoutPayload(array $items, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi',
            'phone' => '081234567890',
            'shipping_address' => ['detail' => 'Jl. Test No. 1', 'lat' => -6.2, 'lng' => 106.8],
            'shipping_option' => ['courier_code' => 'jne', 'label' => 'JNE Reguler'],
            'shipping_cost' => 10000,
            'payment_method' => 'bank_transfer',
            'items' => $items,
        ], $overrides);
    }

    public function test_checkout_ignores_client_sent_price_and_uses_product_price(): void
    {
        Http::fake();
        $this->makeProduct();

        $response = $this->postJson('/checkout', $this->checkoutPayload([
            ['product_id' => 'baju-test', 'qty' => 2, 'price' => 1, 'name' => 'HACKED', 'weight' => 1],
        ]));

        $response->assertOk();
        $this->assertDatabaseHas('order_items', [
            'product_id' => 'baju-test',
            'name' => 'Baju Test',
            'price' => 100000,
            'weight' => 200,
            'qty' => 2,
        ]);
        // subtotal = 2 * 100000, total = subtotal + shipping_cost, discount tidak dikirim
        $this->assertDatabaseHas('orders', ['subtotal' => 200000, 'total' => 210000]);
    }

    public function test_checkout_rejects_product_that_does_not_exist(): void
    {
        Http::fake();

        $response = $this->postJson('/checkout', $this->checkoutPayload([
            ['product_id' => 'tidak-ada', 'qty' => 1],
        ]));

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
    }
}
