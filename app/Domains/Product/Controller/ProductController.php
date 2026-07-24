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
        return Inertia::render('Home', [
            'products' => $this->products->listAll(),
        ]);
    }

    public function catalog(Request $request): Response
    {
        return Inertia::render('Catalog', [
            'products' => $this->products->listAll(),
            'type' => $request->query('type', 'Semua'),
        ]);
    }

    public function show(Product $product): Response
    {
        return Inertia::render('Product', $this->products->findWithRelated($product));
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }
}
