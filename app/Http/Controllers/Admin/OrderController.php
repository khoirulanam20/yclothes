<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::withCount('items')->latest()->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('items.product');
        return view('admin.orders.show', compact('order'));
    }

    public function payment(Request $request, Order $order)
    {
        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'order_status' => 'confirmed',
        ]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Pembayaran dikonfirmasi');
    }

    public function status(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending,confirmed,processed,shipped,delivered,cancelled',
            'courier' => 'nullable|string|max:255',
            'courier_service' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update($validated);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status pesanan diperbarui');
    }
}
