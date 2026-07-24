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
}
