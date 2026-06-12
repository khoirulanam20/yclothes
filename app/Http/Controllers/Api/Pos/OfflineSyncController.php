<?php

namespace App\Http\Controllers\Api\Pos;

use App\Services\PosOfflineSyncService;
use App\Support\Api\PosApiResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function __invoke(Request $request, PosOfflineSyncService $offlineSyncService)
    {
        $validated = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*.client_reference' => ['required', 'uuid'],
            'orders.*.warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'orders.*.created_at' => ['nullable', 'date'],
            'orders.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'orders.*.customer_name' => ['nullable', 'string', 'max:255'],
            'orders.*.customer_phone' => ['nullable', 'string', 'max:30'],
            'orders.*.customer_email' => ['nullable', 'email', 'max:255'],
            'orders.*.items' => ['required', 'array', 'min:1'],
            'orders.*.items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'orders.*.items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'orders.*.items.*.qty' => ['required', 'integer', 'min:1'],
            'orders.*.items.*.discount_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'orders.*.payments' => ['required', 'array', 'min:1'],
            'orders.*.payments.*.method' => ['required', 'in:cash,transfer'],
            'orders.*.payments.*.amount' => ['required', 'integer', 'min:1'],
            'orders.*.payments.*.payment_bank_id' => ['nullable', 'integer', 'exists:payment_banks,id'],
            'orders.*.payments.*.reference' => ['nullable', 'string', 'max:255'],
            'orders.*.notes' => ['nullable', 'string', 'max:2000'],
            'orders.*.coupon_code' => ['nullable', 'prohibited'],
        ]);

        $results = $offlineSyncService->syncBatch(
            $this->posUser($request),
            $validated['orders'],
        );

        return PosApiResponse::success(['results' => $results]);
    }
}
