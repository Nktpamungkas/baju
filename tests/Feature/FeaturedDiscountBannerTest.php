<?php

namespace Tests\Feature;

use App\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedDiscountBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_featured_discount_shows_on_homepage(): void
    {
        Discount::create(['code' => 'HEMAT10', 'type' => 'percent', 'value' => 10, 'active' => true, 'featured' => true]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('discount.code', 'HEMAT10'));
    }

    public function test_featured_but_inactive_discount_does_not_show(): void
    {
        Discount::create(['code' => 'OFF10', 'type' => 'percent', 'value' => 10, 'active' => false, 'featured' => true]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('discount', null));
    }

    public function test_featured_but_expired_discount_does_not_show(): void
    {
        Discount::create(['code' => 'EXPIRED', 'type' => 'percent', 'value' => 10, 'active' => true, 'featured' => true, 'expires_at' => now()->subDay()]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('discount', null));
    }

    public function test_active_but_not_featured_discount_does_not_show(): void
    {
        Discount::create(['code' => 'PRIVATE', 'type' => 'percent', 'value' => 10, 'active' => true, 'featured' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('discount', null));
    }
}
