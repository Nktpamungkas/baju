<?php

namespace App\Domains\Product\Service;

use App\Domains\Product\Repository\ProductRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function listAll(): Collection
    {
        return $this->products->all();
    }

    public function listForAdmin(): Collection
    {
        return $this->products->allOrderedByName();
    }

    public function findWithRelated(Product $product, int $take = 3): array
    {
        return [
            'product' => $product,
            'related' => $this->products->findRelatedByType($product, $take),
        ];
    }

    public function metaFor(Product $product): array
    {
        $price = 'Rp '.number_format($product->price, 0, ',', '.');

        return [
            'title' => "{$product->name} — {$price}",
            'description' => Str::limit($product->desc ?? '', 150),
            'image' => $product->variants[0]['img'] ?? null,
        ];
    }

    public function create(array $data): Product
    {
        $data = $this->applyDefaults($data);
        $data['id'] = $this->generateUniqueId($data['name']);

        return $this->products->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        return $this->products->update($product, $this->applyDefaults($data));
    }

    public function delete(Product $product): void
    {
        $this->products->delete($product);
    }

    public function storeUploadedPhoto(UploadedFile $file): string
    {
        $name = Str::random(16).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('img'), $name);

        return '/img/'.$name;
    }

    private function applyDefaults(array $data): array
    {
        $data['word'] = $data['word'] ?? 'warna';
        $data['variants'] = array_values($data['variants'] ?? []);
        $data['sizeCols'] = $data['sizeCols'] ?? ['Dada', 'Panjang', 'Lengan'];
        $data['sizes'] = $data['sizes'] ?? [['S', 0, 0, 0], ['M', 0, 0, 0], ['L', 0, 0, 0]];

        return $data;
    }

    private function generateUniqueId(string $name): string
    {
        $id = Str::slug($name) ?: 'produk-'.Str::random(6);
        $base = $id;
        $n = 2;

        while ($this->products->existsById($id)) {
            $id = $base.'-'.$n++;
        }

        return $id;
    }
}
