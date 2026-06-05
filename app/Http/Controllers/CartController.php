<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartItemResolver;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\PromotionEngine;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CartPricingService $cartPricing,
        private PromotionEngine $promotionEngine,
        private InventoryService $inventoryService,
        private CartItemResolver $cartItemResolver,
    ) {}

    public function index()
    {
        $pricing = $this->cartPricing->build();

        return Inertia::render('Guest/Cart/Index', [
            'items' => array_map(fn ($r) => ModelSerializer::cartRow($r), $pricing['items']),
            'pricing' => ModelSerializer::cartPricing($pricing),
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'qty' => 'nullable|integer|min:1|max:99',
            'size' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
        ]);

        $qty = $validated['qty'] ?? 1;

        if (! empty($validated['variant_id'])) {
            $variant = ProductVariant::with('parentProduct')->findOrFail($validated['variant_id']);
            if (! $variant->is_active) {
                throw ValidationException::withMessages(['variant_id' => 'Varian tidak tersedia.']);
            }

            $product = $variant->parentProduct;
            if (! $this->inventoryService->canOrder($product, $variant, $qty)) {
                throw ValidationException::withMessages(['qty' => 'Stok tidak mencukupi untuk varian ini.']);
            }

            $itemKey = 'variant-'.$variant->id;
            $cart = $this->cartService->get();

            if (isset($cart[$itemKey])) {
                $newQty = min(99, $cart[$itemKey]['qty'] + $qty);
                if (! $this->inventoryService->canOrder($product, $variant, $newQty)) {
                    throw ValidationException::withMessages(['qty' => 'Stok tidak mencukupi untuk varian ini.']);
                }
                $cart[$itemKey]['qty'] = $newQty;
            } else {
                $cart[$itemKey] = [
                    'id' => $product->id,
                    'variant_id' => $variant->id,
                    'qty' => $qty,
                ];
            }
        } else {
            if (empty($validated['product_id'])) {
                throw ValidationException::withMessages(['product_id' => 'Produk wajib dipilih.']);
            }

            $product = Product::findOrFail($validated['product_id']);
            $this->validateVariant($product, $validated['size'] ?? null, $validated['color'] ?? null);

            if (! $this->inventoryService->canOrder($product, null, $qty)) {
                throw ValidationException::withMessages([
                    'qty' => 'Stok tidak mencukupi untuk produk ini.',
                ]);
            }

            $size = $validated['size'] ?? null;
            $color = $validated['color'] ?? null;
            $itemKey = $product->id.'-'.$size.'-'.$color;
            $cart = $this->cartService->get();

            if (isset($cart[$itemKey])) {
                $newQty = min(99, $cart[$itemKey]['qty'] + $qty);
                if (! $this->inventoryService->canOrder($product, null, $newQty)) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stok tidak mencukupi untuk produk ini.',
                    ]);
                }
                $cart[$itemKey]['qty'] = $newQty;
            } else {
                $cart[$itemKey] = [
                    'id' => $product->id,
                    'size' => $size,
                    'color' => $color,
                    'qty' => $qty,
                ];
            }
        }

        $this->cartService->put($cart);

        return $this->cartActionResponse($request, 'Produk ditambahkan ke cart');
    }

    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($validated['coupon_code']));
        $error = $this->promotionEngine->validateCoupon($code, auth('customer')->id());

        if ($error) {
            return back()->with('error', $error);
        }

        session([CartService::COUPON_SESSION_KEY => $code]);

        return back()->with('success', 'Kupon berhasil diterapkan.');
    }

    public function removeCoupon()
    {
        session()->forget(CartService::COUPON_SESSION_KEY);

        return back()->with('success', 'Kupon dihapus.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:200',
            'qty' => 'required|integer|min:1|max:99',
        ]);

        $cart = $this->cartService->get();
        $itemKey = $validated['key'];

        if (isset($cart[$itemKey])) {
            $resolved = $this->cartItemResolver->resolve($cart[$itemKey]);
            if ($resolved && ! $this->inventoryService->canOrder($resolved['product'], $resolved['variant'], $validated['qty'])) {
                if ($request->header('X-Inertia')) {
                    throw ValidationException::withMessages(['qty' => 'Stok tidak mencukupi']);
                }

                return response()->json(['success' => false, 'message' => 'Stok tidak mencukupi'], 422);
            }
            $cart[$itemKey]['qty'] = $validated['qty'];
            $this->cartService->put($cart);
        }

        return $this->cartActionResponse($request);
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:200',
        ]);

        $cart = $this->cartService->get();
        unset($cart[$validated['key']]);
        $this->cartService->put($cart);

        return $this->cartActionResponse($request, 'Item dihapus dari keranjang');
    }

    private function cartActionResponse(Request $request, ?string $message = null)
    {
        $cart = $this->cartService->get();
        $count = array_sum(array_column($cart, 'qty'));

        if ($request->header('X-Inertia')) {
            return back()->with('success', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(array_filter([
                'success' => true,
                'count' => $count,
                'message' => $message,
            ]));
        }

        return back()->with('success', $message);
    }

    private function validateVariant(Product $product, ?string $size, ?string $color): void
    {
        $sizes = $product->sizes ?? [];
        if ($size !== null && $sizes !== [] && ! in_array($size, $sizes, true)) {
            throw ValidationException::withMessages(['size' => 'Ukuran tidak valid untuk produk ini.']);
        }

        $colors = $product->colors ?? [];
        if ($color !== null && $colors !== []) {
            $validHexes = array_column($colors, 'hex');
            if (! in_array($color, $validHexes, true)) {
                throw ValidationException::withMessages(['color' => 'Warna tidak valid untuk produk ini.']);
            }
        }
    }
}
