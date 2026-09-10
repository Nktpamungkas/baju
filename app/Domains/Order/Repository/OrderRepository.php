<?php

namespace App\Domains\Order\Repository;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function allWithItems(): Collection
    {
        return Order::with('items')->latest()->get();
    }

    public function findByTrackingToken(string $token): ?Order
    {
        return Order::where('tracking_token', $token)->first();
    }

    public function findByMidtransOrderId(string $id): ?Order
    {
        return Order::where('midtrans_order_id', $id)->first();
    }

    public function findByBiteshipOrderId(string $id): ?Order
    {
        return Order::where('biteship_order_id', $id)->first();
    }

    public function findAllByPhone(string $phone): Collection
    {
        return Order::where('phone', $phone)->latest()->get();
    }

    // Pesanan yang belum dibayar dan sudah lewat batas waktu — kandidat auto-expire
    // (lihat OrderService::expireStaleOrders).
    public function findStalePending(int $hours): Collection
    {
        return Order::with('items')
            ->whereIn('payment_status', ['pending', 'menunggu_konfirmasi'])
            ->where('created_at', '<=', now()->subHours($hours))
            ->get();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);

        return $order;
    }
}
