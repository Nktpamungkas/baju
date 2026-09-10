<?php

namespace App\Domains\Review\Repository;

use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;

class ReviewRepository
{
    public function all(): Collection
    {
        return Review::with('order')->latest()->get();
    }

    public function findByOrderId(int $orderId): ?Review
    {
        return Review::where('order_id', $orderId)->first();
    }

    // Dipakai di beranda — testimoni yang admin approve DAN pilih tampil.
    public function featured(int $limit = 6): Collection
    {
        return Review::where('approved', true)->where('featured', true)->latest()->limit($limit)->get();
    }

    public function create(array $data): Review
    {
        return Review::create($data);
    }

    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review;
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }
}
