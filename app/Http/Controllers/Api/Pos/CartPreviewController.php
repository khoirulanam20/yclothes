<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Requests\Api\Pos\CartPreviewRequest;
use App\Services\PosPricingService;
use App\Support\Api\PosApiResponse;

class CartPreviewController extends Controller
{
    public function __invoke(CartPreviewRequest $request, PosPricingService $pricingService)
    {
        $validated = $request->validated();
        $pricing = $pricingService->build(
            $validated['items'],
            (int) $validated['warehouse_id'],
            $validated['coupon_code'] ?? null,
            $validated['customer_id'] ?? null,
        );

        return PosApiResponse::success([
            'lineItems' => $pricingService->previewLineItems($pricing),
            'subtotal' => $pricing['subtotal'],
            'taxAmount' => $pricing['tax_amount'],
            'discountAmount' => $pricing['discount_amount'],
            'grandTotal' => $pricing['grand_total'],
            'couponCode' => $pricing['coupon_code'],
            'taxIncluded' => $pricing['tax_included'],
            'stockWarnings' => $pricing['stock_warnings'],
        ]);
    }
}
