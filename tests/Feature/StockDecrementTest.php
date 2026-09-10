<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StockDecrementTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutPayload(array $items): array
    {
        return [
            'customer_name' => 'Budi',
            'phone' => '081234567890',
            'shipping_address' => ['detail' => 'Jl. Test No. 1', 'lat' => -6.2, 'lng' => 106.8],
            'shipping_option' => ['courier_code' => 'jne', 'label' => 'JNE Reguler'],
            'shipping_cost' => 10000,
            'payment_method' => 'bank_transfer',
            'items' => $items,
        ];
    }

    public function test_checkout_decrements_stock(): void
    {
        Http::fake();
        Product::create([
            'id' => 'baju-stok', 'name' => 'Baju Stok', 'type' => 'Setelan',
            'price' => 50000, 'weight' => 200, 'stock' => 5,
            'variants' => [], 'sizeCols' => [], 'sizes' => [],
        ]);

        $this->postJson('/checkout', $this->checkoutPayload([
            ['product_id' => 'baju-stok', 'qty' => 2],
        ]))->assertOk();

        $this->assertSame(3, Product::find('baju-stok')->stock);
    }

    public function test_checkout_fails_when_stock_is_insufficient(): void
    {
        Http::fake();
        Product::create([
            'id' => 'baju-tipis', 'name' => 'Baju Tipis Stok', 'type' => 'Setelan',
            'price' => 50000, 'weight' => 200, 'stock' => 1,
            'variants' => [], 'sizeCols' => [], 'sizes' => [],
        ]);

        $this->postJson('/checkout', $this->checkoutPayload([
            ['product_id' => 'baju-tipis', 'qty' => 2],
        ]))->assertStatus(422);

        $this->assertSame(1, Product::find('baju-tipis')->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_null_stock_is_treated_as_unlimited(): void
    {
        Http::fake();
        Product::create([
            'id' => 'baju-bebas', 'name' => 'Baju Bebas Stok', 'type' => 'Setelan',
            'price' => 50000, 'weight' => 200, 'stock' => null,
            'variants' => [], 'sizeCols' => [], 'sizes' => [],
        ]);

        $this->postJson('/checkout', $this->checkoutPayload([
            ['product_id' => 'baju-bebas', 'qty' => 1000],
        ]))->assertOk();

        $this->assertNull(Product::find('baju-bebas')->stock);
    }
}
