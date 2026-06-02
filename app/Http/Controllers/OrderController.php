<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function success(Order $order)
    {
        return view('order.success', compact('order'));
    }

    public function track()
    {
        return view('order.track');
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('customer_email', $validated['email'])
            ->first();

        if (! $order) {
            return redirect()->route('order.track')->with('error', 'Pesanan tidak ditemukan. Periksa nomor pesanan dan email.');
        }

        grant_order_access($order);

        return redirect()->to(order_public_url('order.show', $order));
    }

    public function show(Order $order)
    {
        $order->load('items.product');

        return view('order.show', compact('order'));
    }
}
