<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(string $fulfillmentStatus): Order
    {
        return Order::create([
            'tracking_token' => 'tok-'.uniqid(),
            'customer_name' => 'Budi', 'phone' => '081234567890',
            'shipping_address' => ['detail' => 'x', 'lat' => -6, 'lng' => 106],
            'subtotal' => 100000, 'total' => 100000,
            'payment_status' => 'settlement', 'fulfillment_status' => $fulfillmentStatus,
        ]);
    }

    public function test_review_is_rejected_for_order_that_is_not_yet_delivered(): void
    {
        $order = $this->makeOrder('dropping_off');

        $this->postJson("/pesanan/{$order->tracking_token}/ulasan", [
            'rating' => 5, 'comment' => 'Bagus banget!',
        ])->assertStatus(422);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_delivered_order_can_be_reviewed_but_only_once(): void
    {
        $order = $this->makeOrder('delivered');

        $this->postJson("/pesanan/{$order->tracking_token}/ulasan", [
            'rating' => 5, 'comment' => 'Barangnya bagus, cepat sampai.',
        ])->assertOk();

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'rating' => 5,
            'approved' => false, // wajib di-approve admin dulu sebelum tampil publik
            'featured' => false,
        ]);

        // Submit kedua ke pesanan yang sama harus ditolak.
        $this->postJson("/pesanan/{$order->tracking_token}/ulasan", [
            'rating' => 1, 'comment' => 'Coba review lagi.',
        ])->assertStatus(422);

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_unapproved_review_never_appears_on_homepage(): void
    {
        $order = $this->makeOrder('delivered');
        $this->postJson("/pesanan/{$order->tracking_token}/ulasan", [
            'rating' => 5, 'comment' => 'Mantap.',
        ])->assertOk();

        $this->get('/')->assertInertia(fn ($page) => $page->where('reviews', []));
    }
}
