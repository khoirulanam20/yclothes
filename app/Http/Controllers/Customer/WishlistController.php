<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Support\ModelSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function index()
    {
        $items = Auth::guard('customer')->user()
            ->wishlists()
            ->with('product.category')
            ->latest('created_at')
            ->get();

        $products = $items->map(fn ($item) => $item->product)->filter();

        return Inertia::render('Guest/Account/Wishlist', [
            'products' => ModelSerializer::collection($products, [ModelSerializer::class, 'product']),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $customer = Auth::guard('customer')->user();
        $existing = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['success' => true, 'in_wishlist' => false]);
        }

        Wishlist::create([
            'customer_id' => $customer->id,
            'product_id' => $validated['product_id'],
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'in_wishlist' => true]);
    }
}
