<?php

namespace App\Support\Serializers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ModelSerializer;

class OrderSerializer
{
    public static function order(Order $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'customerName' => $order->customer_name,
            'customerPhone' => $order->customer_phone,
            'customerEmail' => $order->customer_email,
            'shippingAddress' => $order->shipping_address,
            'provinceName' => $order->province_name,
            'regencyName' => $order->regency_name,
            'districtName' => $order->district_name,
            'villageName' => $order->village_name,
            'postalCode' => $order->postal_code,
            'fullShippingAddress' => $order->fullShippingAddress(),
            'shippingCity' => $order->shipping_city,
            'shippingCost' => $order->shipping_cost,
            'shippingMethod' => $order->shipping_method,
            'totalPrice' => $order->total_price,
            'taxAmount' => $order->tax_amount,
            'discountAmount' => $order->discount_amount,
            'couponCode' => $order->coupon_code,
            'grandTotal' => $order->grand_total,
            'uniquePaymentAmount' => $order->unique_payment_amount,
            'paymentGatewayData' => $order->payment_gateway_data,
            'paymentMethod' => $order->payment_method,
            'paymentStatus' => $order->payment_status,
            'paymentConfirmationStatus' => $order->payment_confirmation_status,
            'orderStatus' => $order->order_status,
            'isReplacement' => (bool) $order->is_replacement,
            'sourceReturnRequestId' => $order->source_return_request_id,
            'bankName' => $order->bank_name,
            'bankAccountNumber' => $order->bank_account_number,
            'bankAccountName' => $order->bank_account_name,
            'paymentDueAt' => $order->payment_due_at?->toIso8601String(),
            'paidAt' => $order->paid_at?->toIso8601String(),
            'deliveredAt' => $order->delivered_at?->toIso8601String(),
            'completedAt' => $order->completed_at?->toIso8601String(),
            'courier' => $order->courier,
            'courierService' => $order->courier_service,
            'trackingNumber' => $order->tracking_number,
            'notes' => $order->notes,
            'refundStatus' => $order->refund_status,
            'refundedAmount' => $order->refunded_amount,
            'createdAt' => $order->created_at?->toIso8601String(),
        ];

        if ($detailed && $order->relationLoaded('items')) {
            $data['items'] = $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'productName' => $item->product_name,
                'qty' => $item->qty,
                'unitPrice' => $item->product_price,
                'subtotal' => $item->subtotal,
                'size' => $item->size,
                'color' => $item->color,
                'imageUrl' => $item->product?->image_url,
            ])->values()->all();
        }

        return $data;
    }

    public static function orderSummary(Order $order): array
    {
        $data = [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'customerName' => $order->customer_name,
            'grandTotal' => $order->grand_total,
            'orderStatus' => $order->order_status,
            'paymentStatus' => $order->payment_status,
            'paymentConfirmationStatus' => $order->payment_confirmation_status,
            'createdAt' => $order->created_at?->toIso8601String(),
            'itemsCount' => $order->items_count ?? null,
        ];

        if ($order->relationLoaded('items')) {
            $data['itemsCount'] = $order->items->count();
            $data['previewItems'] = $order->items->take(3)->map(fn ($item) => [
                'productName' => $item->product_name,
                'imageUrl' => $item->product?->image_url,
                'qty' => $item->qty,
            ])->values()->all();

            $data['canReview'] = $order->canCustomerReview() && $order->hasUnreviewedItems();
        }

        return $data;
    }

    public static function orderStatusHistory($history): array
    {
        return [
            'id' => $history->id,
            'fromStatus' => $history->from_status,
            'toStatus' => $history->to_status,
            'note' => $history->note,
            'createdAt' => $history->created_at?->toIso8601String(),
        ];
    }

    public static function paymentConfirmation($confirmation): array
    {
        return [
            'id' => $confirmation->id,
            'amountClaimed' => $confirmation->amount_claimed,
            'transferDate' => $confirmation->transfer_date?->format('Y-m-d'),
            'senderName' => $confirmation->sender_name,
            'proofImageUrl' => $confirmation->proof_image ? storage_url($confirmation->proof_image) : null,
            'status' => $confirmation->status,
            'adminNote' => $confirmation->admin_note,
            'bank' => $confirmation->relationLoaded('paymentBank') && $confirmation->paymentBank
                ? ModelSerializer::paymentBank($confirmation->paymentBank)
                : null,
            'createdAt' => $confirmation->created_at?->toIso8601String(),
        ];
    }
}
