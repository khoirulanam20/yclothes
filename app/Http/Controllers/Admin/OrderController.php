<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InventoryService;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(private InventoryService $inventoryService) {}

    public function index()
    {
        $orders = Order::withCount('items')->latest()->paginate(20);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => ModelSerializer::paginated($orders, [ModelSerializer::class, 'orderSummary']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items.product');

        return Inertia::render('Admin/Orders/Show', [
            'order' => ModelSerializer::order($order, true),
        ]);
    }

    public function payment(Request $request, Order $order)
    {
        if ($order->payment_status !== 'paid') {
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'order_status' => 'confirmed',
            ]);

            $this->inventoryService->decrementOnPaid($order->fresh());
        }

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
