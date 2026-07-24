<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Products', [
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function orders(): Response
    {
        return Inertia::render('Admin/Orders', [
            'orders' => Order::with('items')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $id = Str::slug($data['name']) ?: 'produk-'.Str::random(6);
        $base = $id; $n = 2;
        while (Product::whereKey($id)->exists()) { $id = $base.'-'.$n++; }

        Product::create(array_merge($data, ['id' => $id]));

        return redirect()->route('admin.products');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request));

        return redirect()->route('admin.products');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products');
    }

    // Upload satu foto varian → simpan ke public/img → balikан URL.
    public function upload(Request $request): JsonResponse
    {
        $request->validate(['photo' => 'required|image|max:6144']);
        $file = $request->file('photo');
        $name = Str::random(16).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('img'), $name);

        return response()->json(['url' => '/img/'.$name]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
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

        $data['word']     = $data['word'] ?? 'warna';
        $data['variants'] = array_values($data['variants'] ?? []);
        $data['sizeCols'] = $data['sizeCols'] ?? ['Dada', 'Panjang', 'Lengan'];
        $data['sizes']    = $data['sizes'] ?? [['S', 0, 0, 0], ['M', 0, 0, 0], ['L', 0, 0, 0]];

        return $data;
    }
}
