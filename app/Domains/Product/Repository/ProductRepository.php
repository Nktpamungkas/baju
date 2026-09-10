<?php

namespace App\Domains\Product\Repository;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function all(): Collection
    {
        return Product::all();
    }

    public function allOrderedByName(): Collection
    {
        return Product::orderBy('name')->get();
    }

    public function findRelatedByType(Product $product, int $take = 3): Collection
    {
        return Product::where('type', $product->type)
            ->where('id', '!=', $product->id)
            ->take($take)
            ->get();
    }

    public function existsById(string $id): bool
    {
        return Product::whereKey($id)->exists();
    }

    public function find(string $id): ?Product
    {
        return Product::find($id);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    // Satu UPDATE atomik dengan guard di WHERE — aman dari race condition dua checkout
    // barengan tanpa perlu row lock manual (SQLite serialize semua write di level file).
    // stock NULL (tidak dilacak) otomatis lolos & tetap NULL setelah dikurangi.
    public function decrementStock(string $id, int $qty): bool
    {
        return Product::whereKey($id)
            ->where(fn ($q) => $q->whereNull('stock')->orWhere('stock', '>=', $qty))
            ->decrement('stock', $qty) > 0;
    }

    public function incrementStock(string $id, int $qty): void
    {
        Product::whereKey($id)->increment('stock', $qty);
    }
}
