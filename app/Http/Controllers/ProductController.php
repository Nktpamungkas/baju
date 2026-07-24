<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Home', [
            'products' => Product::all(),
        ]);
    }

    public function catalog(Request $request): Response
    {
        return Inertia::render('Catalog', [
            'products' => Product::all(),
            'type'     => $request->query('type', 'Semua'),
        ]);
    }

    public function show(Product $product): Response
    {
        return Inertia::render('Product', [
            'product' => $product,
            'related' => Product::where('type', $product->type)
                ->where('id', '!=', $product->id)
                ->take(3)->get(),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }
}
