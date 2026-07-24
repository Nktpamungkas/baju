<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(database_path('data/products.json'));
        $products = json_decode($json, true);

        foreach ($products as $p) {
            Product::updateOrCreate(['id' => $p['id']], [
                'name'     => $p['name'],
                'type'     => $p['type'],
                'price'    => $p['price'],
                'word'     => $p['word'] ?? 'warna',
                'material' => $p['material'] ?? null,
                'desc'     => $p['desc'] ?? null,
                'shopee'   => $p['shopee'] ?? null,
                'toko'     => $p['toko'] ?? null,
                'variants' => $p['variants'],
                'sizeCols' => $p['sizeCols'],
                'sizes'    => $p['sizes'],
            ]);
        }
    }
}
