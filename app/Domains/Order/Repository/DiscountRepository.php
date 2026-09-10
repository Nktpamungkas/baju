<?php

namespace App\Domains\Order\Repository;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Collection;

class DiscountRepository
{
    public function all(): Collection
    {
        return Discount::latest()->get();
    }

    public function findActiveByCode(string $code): ?Discount
    {
        return Discount::where('code', $code)->where('active', true)->first();
    }

    // Dipakai buat banner promo di beranda — cuma yang admin tandai "tampilkan" DAN
    // masih berlaku (belum kedaluwarsa).
    public function activeFeatured(): ?Discount
    {
        return Discount::where('active', true)
            ->where('featured', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->first();
    }

    public function create(array $data): Discount
    {
        return Discount::create($data);
    }

    public function update(Discount $discount, array $data): Discount
    {
        $discount->update($data);

        return $discount;
    }

    public function delete(Discount $discount): void
    {
        $discount->delete();
    }
}
