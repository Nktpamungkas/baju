<?php

namespace App\Domains\Review\Service;

use App\Domains\Product\Service\ProductService;
use App\Domains\Review\Repository\ReviewRepository;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ReviewService
{
    public function __construct(
        private ReviewRepository $reviews,
        private ProductService $productService,
    ) {
    }

    // Cuma pembeli dengan pesanan yang BENERAN sudah sampai yang boleh kasih ulasan,
    // dan cuma sekali per pesanan (guard unik di kolom order_id + guard di sini).
    public function submit(Order $order, array $data, ?UploadedFile $photo): Review
    {
        if ($order->fulfillment_status !== 'delivered') {
            throw new InvalidArgumentException('Ulasan cuma bisa diberikan untuk pesanan yang sudah sampai.');
        }

        if ($this->reviews->findByOrderId($order->id)) {
            throw new InvalidArgumentException('Pesanan ini sudah pernah diberi ulasan.');
        }

        return $this->reviews->create([
            'order_id'      => $order->id,
            'customer_name' => $order->customer_name,
            'rating'        => $data['rating'],
            'comment'       => $data['comment'],
            'photo'         => $photo ? $this->productService->storeUploadedPhoto($photo) : null,
        ]);
    }

    public function findByOrderId(int $orderId): ?Review
    {
        return $this->reviews->findByOrderId($orderId);
    }

    public function all(): Collection
    {
        return $this->reviews->all();
    }

    public function approve(Review $review): void
    {
        $this->reviews->update($review, ['approved' => true]);
    }

    public function toggleFeatured(Review $review): void
    {
        $this->reviews->update($review, ['featured' => ! $review->featured]);
    }

    public function delete(Review $review): void
    {
        $this->reviews->delete($review);
    }

    public function featuredForHome(): Collection
    {
        return $this->reviews->featured();
    }
}
