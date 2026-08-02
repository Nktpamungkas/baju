<?php

namespace App\Domains\Product\Controller;

use App\Domains\Product\Service\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController
{
    public function __construct(private ProductService $products)
    {
    }

    public function home(): Response
    {
        view()->share('meta', [
            'title' => config('app.name', 'NALE').' — Baju Anak yang Nyaman & Tenang',
            'description' => 'Katalog baju anak NALE — katun organik, linen, dan muslin pilihan. Beli langsung lewat Shopee & Tokopedia.',
        ]);

        return Inertia::render('Home', [
            'products' => $this->products->listAll(),
        ]);
    }

    public function catalog(Request $request): Response
    {
        view()->share('meta', [
            'title' => 'Katalog — '.config('app.name', 'NALE'),
            'description' => 'Semua produk baju anak NALE dalam satu katalog.',
        ]);

        return Inertia::render('Catalog', [
            'products' => $this->products->listAll(),
            'type' => $request->query('type', 'Semua'),
        ]);
    }

    public function show(Product $product): Response
    {
        view()->share('meta', $this->products->metaFor($product));

        return Inertia::render('Product', $this->products->findWithRelated($product));
    }

    public function about(): Response
    {
        view()->share('meta', [
            'title' => 'Tentang — '.config('app.name', 'NALE'),
            'description' => 'Cerita di balik NALE — bahan pilihan, pewarna aman, dan jahitan rapi untuk anak.',
        ]);

        return Inertia::render('About');
    }
}
