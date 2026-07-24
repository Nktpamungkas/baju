<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(): Response
    {
        // Keranjang disimpan di client (Pinia + localStorage).
        return Inertia::render('Cart');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:120',
            'phone'         => 'required|string|max:40',
            'address'       => 'required|string|max:500',
            'items'                => 'required|array|min:1',
            'items.*.id'           => 'required|string',
            'items.*.name'         => 'required|string',
            'items.*.variant'      => 'nullable|string',
            'items.*.size'         => 'nullable|string',
            'items.*.price'        => 'required|integer|min:0',
            'items.*.qty'          => 'required|integer|min:1',
        ]);

        $subtotal = collect($data['items'])->sum(fn ($i) => $i['price'] * $i['qty']);
        $shipping = ($subtotal === 0 || $subtotal >= 250000) ? 0 : 15000;

        $order = Order::create([
            'customer_name' => $data['customer_name'],
            'phone'         => $data['phone'],
            'address'       => $data['address'],
            'subtotal'      => $subtotal,
            'shipping'      => $shipping,
            'total'         => $subtotal + $shipping,
            'status'        => 'pending',
        ]);

        foreach ($data['items'] as $i) {
            $order->items()->create([
                'product_id' => $i['id'],
                'name'       => $i['name'],
                'variant'    => $i['variant'] ?? null,
                'size'       => $i['size'] ?? null,
                'price'      => $i['price'],
                'qty'        => $i['qty'],
            ]);
        }

        // TODO: integrasi payment gateway (Midtrans/Xendit) di sini.
        return redirect()->route('cart')->with('order_id', $order->id);
    }
}
