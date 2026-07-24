<?php

namespace App\Domains\Product\Controller;

use App\Domains\Product\Service\ProductService;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductAdminController
{
    public function __construct(private ProductService $products)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Products', [
            'products' => $this->products->listForAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->products->create($this->validated($request));

        return redirect()->route('admin.products');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $this->validated($request));

        return redirect()->route('admin.products');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()->route('admin.products');
    }

    // Upload satu foto varian → simpan ke public/img → balikan URL.
    public function upload(Request $request): JsonResponse
    {
        $request->validate(['photo' => 'required|image|max:6144']);

        return response()->json([
            'url' => $this->products->storeUploadedPhoto($request->file('photo')),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'     => 'required|string|max:120',
            'type'     => 'required|string|max:40',
            'price'    => 'required|integer|min:0',
            'word'     => 'nullable|string|max:20',
            'material' => 'nullable|string|max:200',
            'desc'     => 'nullable|string|max:2000',
            'shopee'   => 'nullable|string|max:400',
            'toko'     => 'nullable|string|max:400',
            'variants'          => 'array',
            'variants.*.name'   => 'required|string|max:80',
            'variants.*.img'    => 'nullable|string|max:400',
            'sizeCols'          => 'array',
            'sizes'             => 'array',
        ]);
    }
}
