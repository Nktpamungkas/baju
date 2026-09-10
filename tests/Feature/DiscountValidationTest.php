<?php

namespace Tests\Feature;

use App\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_discount_is_rejected(): void
    {
        Discount::create(['code' => 'EXPIRED10', 'type' => 'percent', 'value' => 10, 'expires_at' => now()->subDay()]);

        $this->postJson('/checkout/diskon', ['code' => 'EXPIRED10', 'subtotal' => 100000])
            ->assertStatus(422);
    }

    public function test_inactive_discount_is_rejected(): void
    {
        Discount::create(['code' => 'OFF10', 'type' => 'percent', 'value' => 10, 'active' => false]);

        $this->postJson('/checkout/diskon', ['code' => 'OFF10', 'subtotal' => 100000])
            ->assertStatus(422);
    }

    public function test_discount_below_min_subtotal_is_rejected(): void
    {
        Discount::create(['code' => 'MIN50K', 'type' => 'fixed', 'value' => 5000, 'min_subtotal' => 50000]);

        $this->postJson('/checkout/diskon', ['code' => 'MIN50K', 'subtotal' => 10000])
            ->assertStatus(422);
    }

    public function test_valid_percent_discount_is_accepted_and_computed_correctly(): void
    {
        Discount::create(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 0]);

        $this->postJson('/checkout/diskon', ['code' => 'HEMAT10', 'subtotal' => 100000])
            ->assertOk()
            ->assertJson(['code' => 'HEMAT10', 'amount' => 10000]);
    }

    public function test_fixed_discount_never_exceeds_subtotal(): void
    {
        Discount::create(['code' => 'GEDE', 'type' => 'fixed', 'value' => 999999, 'min_subtotal' => 0]);

        $this->postJson('/checkout/diskon', ['code' => 'GEDE', 'subtotal' => 50000])
            ->assertOk()
            ->assertJson(['amount' => 50000]);
    }
}
