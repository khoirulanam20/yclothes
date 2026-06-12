<?php

namespace App\Http\Controllers\Api\Pos;

use App\Models\PosHeldCart;
use App\Services\PosHeldCartService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class HeldCartController extends Controller
{
    public function index(Request $request, PosHeldCartService $heldCartService)
    {
        $warehouseId = $request->filled('warehouse_id')
            ? $request->integer('warehouse_id')
            : null;

        $held = $heldCartService->listForUser($this->posUser($request), $warehouseId);

        return PosApiResponse::success(
            $held->map(fn (PosHeldCart $cart) => $heldCartService->serialize($cart))->values()->all(),
        );
    }

    public function store(Request $request, PosHeldCartService $heldCartService)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $held = $heldCartService->hold($this->posUser($request), $validated);

        return PosApiResponse::success(
            $heldCartService->serialize($held),
            [],
            201,
        );
    }

    public function resume(PosHeldCart $heldCart, Request $request, PosHeldCartService $heldCartService)
    {
        $payload = $heldCartService->resume($heldCart, $this->posUser($request));

        return PosApiResponse::success($payload);
    }

    public function destroy(PosHeldCart $heldCart, Request $request, PosHeldCartService $heldCartService)
    {
        $heldCartService->discard($heldCart, $this->posUser($request));

        return PosApiResponse::success(['deleted' => true]);
    }
}
