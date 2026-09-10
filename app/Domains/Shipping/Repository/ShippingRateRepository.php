<?php

namespace App\Domains\Shipping\Repository;

use App\Models\ShippingRateQuote;

class ShippingRateRepository
{
    private const RADIUS_METERS = 1000;
    private const TTL_HOURS = 24 * 7; // ongkir jarang berubah harian, aman di-cache seminggu

    public function findNearby(float $lat, float $lng, int $weight): ?ShippingRateQuote
    {
        $latDelta = self::RADIUS_METERS / 111320;
        $lngDelta = self::RADIUS_METERS / (111320 * max(cos(deg2rad($lat)), 0.01));

        $candidates = ShippingRateQuote::query()
            ->where('weight', $weight)
            ->where('created_at', '>=', now()->subHours(self::TTL_HOURS))
            ->whereBetween('lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('lng', [$lng - $lngDelta, $lng + $lngDelta])
            ->get();

        foreach ($candidates as $quote) {
            if ($this->distanceMeters($lat, $lng, $quote->lat, $quote->lng) <= self::RADIUS_METERS) {
                return $quote;
            }
        }

        return null;
    }

    public function store(float $lat, float $lng, int $weight, array $options): void
    {
        ShippingRateQuote::create(compact('lat', 'lng', 'weight', 'options'));
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
