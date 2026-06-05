<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ModelSerializer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::guard('customer')->user()
            ->orders()
            ->with('items')
            ->latest()
            ->paginate(10);

        return Inertia::render('Guest/Account/Orders', [
            'orders' => collect($orders->items())->map([ModelSerializer::class, 'orderSummary'])->values()->all(),
        ]);
    }

    public function show(Order $order)
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 403);

        $order->load('items.product');

        return Inertia::render('Guest/Order/Show', [
            'order' => ModelSerializer::order($order, true),
        ]);
    }
}
