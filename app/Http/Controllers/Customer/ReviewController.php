<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function create(Product $product): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $eligibleOrder = Order::where('customer_id', $customer->id)
            ->whereIn('order_status', ['delivered', 'completed'])
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();

        if (! $eligibleOrder) {
            return redirect()->route('products.show', $product->slug)
                ->with('error', 'Anda hanya bisa review produk dari pesanan yang sudah selesai.');
        }

        $existing = Review::where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return redirect()->route('products.show', $product->slug)
                ->with('error', 'Anda sudah memberikan review untuk produk ini.');
        }

        return redirect()->route('products.show', $product->slug);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'order_item_id' => 'nullable|integer|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);

        $order = Order::where('id', $validated['order_id'])
            ->where('customer_id', $customer->id)
            ->whereIn('order_status', ['delivered', 'completed'])
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->firstOrFail();

        $autoApprove = setting_bool('auto_approve_reviews');

        Review::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'order_item_id' => $validated['order_item_id'] ?? null,
            'rating' => $validated['rating'],
            'review' => $validated['review'],
            'is_approved' => $autoApprove,
            'created_at' => now(),
        ]);

        return redirect()->route('products.show', $product->slug)
            ->with('success', $autoApprove
                ? 'Review berhasil dikirim.'
                : 'Review berhasil dikirim dan menunggu persetujuan admin.');
    }
}
