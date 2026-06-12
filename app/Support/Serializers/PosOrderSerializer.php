<?php

namespace App\Support\Serializers;

use App\Models\Order;
use App\Models\PosOrderPayment;

class PosOrderSerializer
{
    public static function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'customerName' => $order->customer_name,
            'customerPhone' => $order->customer_phone,
            'grandTotal' => (int) $order->grand_total,
            'totalQty' => (int) ($order->total_qty ?? ($order->relationLoaded('items') ? $order->items->sum('qty') : 0)),
            'paymentMethod' => $order->payment_method,
            'paymentStatus' => $order->payment_status,
            'orderStatus' => $order->order_status,
            'warehouseId' => $order->warehouse_id,
            'posShiftId' => $order->pos_shift_id,
            'createdAt' => $order->created_at?->toIso8601String(),
        ];
    }

    public static function detail(Order $order): array
    {
        $data = self::summary($order);
        $data['subtotal'] = (int) $order->total_price;
        $data['taxAmount'] = (int) $order->tax_amount;
        $data['discountAmount'] = (int) $order->discount_amount;
        $data['couponCode'] = $order->coupon_code;
        $data['notes'] = $order->notes;
        $data['customerId'] = $order->customer_id;
        $data['customerEmail'] = $order->customer_email;
        $data['paidAt'] = $order->paid_at?->toIso8601String();
        $data['items'] = $order->relationLoaded('items')
            ? $order->items->map(fn ($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'variantId' => $item->product_variant_id,
                'sku' => $item->sku,
                'productName' => $item->product_name,
                'productPrice' => (int) $item->product_price,
                'qty' => (int) $item->qty,
                'subtotal' => (int) $item->subtotal,
                'size' => $item->size,
                'color' => $item->color,
            ])->values()->all()
            : [];
        $data['payments'] = $order->relationLoaded('posPayments')
            ? $order->posPayments->map(fn (PosOrderPayment $payment) => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => (int) $payment->amount,
                'paymentBankId' => $payment->payment_bank_id,
                'reference' => $payment->reference,
            ])->values()->all()
            : [];

        return $data;
    }

    public static function receipt(Order $order): array
    {
        $detail = self::detail($order);

        return array_merge($detail, [
            'storeName' => setting('store_name', config('app.name')),
            'storeAddress' => setting('store_address'),
            'storePhone' => setting('store_phone'),
            'cashierName' => $order->relationLoaded('createdByUser')
                ? $order->createdByUser?->name
                : null,
            'warehouseName' => $order->relationLoaded('warehouse')
                ? $order->warehouse?->name
                : null,
        ]);
    }
}
