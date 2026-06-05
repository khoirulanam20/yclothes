<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\CmsPage;
use App\Services\CmsLayoutService;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Slider;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ModelSerializer
{
    public static function product(Product $product, bool $detailed = false): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'salePrice' => $product->sale_price,
            'finalPrice' => $product->final_price,
            'catalogUnitPrice' => $product->getAttribute('catalog_unit_price'),
            'catalogHasDiscount' => (bool) $product->getAttribute('catalog_has_discount'),
            'imageUrl' => $product->image_url,
            'badge' => $product->badge,
            'discountPercentage' => $product->discount_percentage,
            'category' => $product->relationLoaded('category') && $product->category
                ? self::category($product->category)
                : null,
            'type' => $product->type?->value ?? $product->type,
        ];

        if ($detailed) {
            $data['imagesUrl'] = $product->images_url;
            $data['sizes'] = $product->sizes;
            $data['colors'] = $product->colors;
            $data['ratingAvg'] = (float) $product->rating_avg;
            $data['reviewCount'] = $product->review_count;
            $data['trackStock'] = $product->track_stock;
            $data['variants'] = $product->relationLoaded('activeVariants')
                ? $product->activeVariants->map(fn ($v) => self::variant($v))->values()->all()
                : [];
        }

        return $data;
    }

    public static function variant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'finalPrice' => $variant->final_price,
            'imageUrl' => $variant->image_url,
            'attributeValues' => $variant->attribute_values,
            'isActive' => $variant->is_active,
        ];
    }

    public static function category(Category $category, bool $withChildren = false): array
    {
        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'imageUrl' => $category->image_url,
            'order' => $category->order,
            'parentId' => $category->parent_id,
            'productsCount' => $category->products_count ?? null,
            'childrenCount' => $category->children_count ?? null,
        ];

        if ($withChildren && $category->relationLoaded('children')) {
            $data['children'] = $category->children
                ->map(fn (Category $child) => self::category($child, true))
                ->values()
                ->all();
        }

        return $data;
    }

    public static function slider(Slider $slider): array
    {
        return [
            'id' => $slider->id,
            'title' => $slider->title,
            'imageUrl' => $slider->image_url,
            'linkUrl' => $slider->link_url,
            'sortOrder' => $slider->sort_order,
            'isActive' => (bool) $slider->is_active,
        ];
    }

    public static function adminProduct(Product $product): array
    {
        return array_merge(self::product($product), [
            'categoryId' => $product->category_id,
            'isFeatured' => (bool) $product->is_featured,
            'trackStock' => (bool) $product->track_stock,
            'allowBackorder' => (bool) $product->allow_backorder,
            'weight' => $product->weight,
        ]);
    }

    public static function blogPost(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featuredImageUrl' => $post->featured_image_url,
            'author' => $post->author,
            'publishedAt' => $post->published_at?->toIso8601String(),
            'status' => $post->status,
        ];
    }

    public static function cmsPage(CmsPage $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'layoutJson' => $page->layout_json,
            'layoutVersion' => $page->layout_version,
            'bannerUrl' => $page->banner_url,
            'metaTitle' => $page->meta_title,
            'metaDescription' => $page->meta_description,
            'status' => $page->status,
            'hasLayout' => app(CmsLayoutService::class)->hasRenderableContent($page->layout_json),
        ];
    }

    public static function paginated(LengthAwarePaginator $paginator, callable $mapper): array
    {
        return [
            'data' => collect($paginator->items())->map($mapper)->values()->all(),
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public static function collection(Collection $items, callable $mapper): array
    {
        return $items->map($mapper)->values()->all();
    }

    public static function cartRow(array $row): array
    {
        return [
            'key' => $row['key'],
            'product' => self::product($row['product']),
            'size' => $row['size'],
            'color' => $row['color'],
            'sku' => $row['sku'],
            'productName' => $row['product_name'],
            'qty' => $row['qty'],
            'unitPrice' => $row['unit_price'],
            'subtotal' => $row['subtotal'],
        ];
    }

    public static function cartPricing(array $pricing): array
    {
        return [
            'items' => array_map(fn ($r) => self::cartRow($r), $pricing['items']),
            'subtotal' => $pricing['subtotal'],
            'taxAmount' => $pricing['tax_amount'],
            'taxBreakdown' => $pricing['tax_breakdown'],
            'discountAmount' => $pricing['discount_amount'],
            'freeShipping' => $pricing['free_shipping'],
            'couponCode' => $pricing['coupon_code'],
            'freeShippingProgress' => $pricing['free_shipping_progress'],
            'backorderNotes' => $pricing['backorder_notes'],
            'taxIncluded' => $pricing['tax_included'],
            'totalWeight' => $pricing['total_weight'],
            'totalQty' => $pricing['total_qty'],
        ];
    }

    public static function review(Review $review): array
    {
        $comment = $review->comment ?? $review->review ?? null;

        return [
            'id' => $review->id,
            'orderItemId' => $review->order_item_id,
            'rating' => $review->rating,
            'comment' => $comment,
            'customerName' => $review->customer?->name ?? 'Anonim',
            'createdAt' => $review->created_at?->toIso8601String(),
            'isApproved' => (bool) $review->is_approved,
            'product' => $review->relationLoaded('product') && $review->product
                ? ['id' => $review->product->id, 'name' => $review->product->name]
                : null,
        ];
    }

    public static function attribute(Attribute $attribute): array
    {
        return [
            'id' => $attribute->id,
            'code' => $attribute->code,
            'name' => $attribute->name,
            'type' => $attribute->type,
            'options' => $attribute->relationLoaded('options')
                ? $attribute->options->map(fn ($o) => ['id' => $o->id, 'value' => $o->value])->values()->all()
                : [],
        ];
    }

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

    public static function shippingCity($city, ?int $calculatedCost = null): array
    {
        return [
            'id' => $city->id,
            'cityName' => $city->city_name,
            'regencyCode' => $city->regency_code,
            'regencyName' => $city->regency_name,
            'province' => $city->province_name ?? $city->province ?? null,
            'calculatedCost' => $calculatedCost,
        ];
    }

    public static function paymentBank($bank): array
    {
        return [
            'id' => $bank->id,
            'bankName' => $bank->bank_name,
            'accountNumber' => $bank->account_number,
            'accountName' => $bank->account_name,
            'isActive' => (bool) ($bank->is_active ?? true),
        ];
    }

    public static function shippingCostRecord($cost): array
    {
        return [
            'id' => $cost->id,
            'cityName' => $cost->city_name,
            'regencyCode' => $cost->regency_code,
            'regencyName' => $cost->regency_name,
            'provinceName' => $cost->province_name,
            'cost' => $cost->cost,
            'costPerKg' => $cost->cost_per_kg,
            'isActive' => (bool) $cost->is_active,
        ];
    }

    public static function customer($customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatarUrl' => $customer->avatar_url ?? null,
            'emailVerified' => (bool) $customer->email_verified_at,
        ];
    }

    public static function customerAddressCheckout($address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipientName' => $address->recipient_name,
            'phone' => $address->phone,
            'streetAddress' => $address->street_address,
            'provinceCode' => $address->province_code,
            'provinceName' => $address->province_name ?? $address->province,
            'regencyCode' => $address->regency_code,
            'regencyName' => $address->regency_name ?? $address->city,
            'districtCode' => $address->district_code,
            'districtName' => $address->district_name,
            'villageCode' => $address->village_code,
            'villageName' => $address->village_name,
            'postalCode' => $address->postal_code,
            'city' => $address->regency_name ?? $address->city,
        ];
    }

    public static function customerAddress($address): array
    {
        return array_merge(self::customerAddressCheckout($address), [
            'phone' => $address->phone,
            'province' => $address->province,
            'isDefault' => (bool) $address->is_default,
        ]);
    }

    public static function activityLog(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'createdAt' => $log->created_at?->toIso8601String(),
            'user' => $log->relationLoaded('user') && $log->user
                ? ['id' => $log->user->id, 'name' => $log->user->name]
                : null,
        ];
    }

    public static function lowStockItem(Inventory $inventory): array
    {
        return [
            'id' => $inventory->id,
            'stock' => $inventory->stock,
            'product' => $inventory->relationLoaded('product') && $inventory->product
                ? ['id' => $inventory->product->id, 'name' => $inventory->product->name]
                : null,
            'warehouse' => $inventory->relationLoaded('warehouse') && $inventory->warehouse
                ? ['id' => $inventory->warehouse->id, 'name' => $inventory->warehouse->name]
                : null,
        ];
    }

    public static function staffUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isAdmin' => (bool) $user->is_admin,
            'adminRoleId' => $user->admin_role_id,
            'role' => $user->relationLoaded('adminRole') && $user->adminRole
                ? ['id' => $user->adminRole->id, 'name' => $user->adminRole->name]
                : null,
        ];
    }

    public static function orderSummary(Order $order): array
    {
        return [
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
                ? self::paymentBank($confirmation->paymentBank)
                : null,
            'createdAt' => $confirmation->created_at?->toIso8601String(),
        ];
    }

    public static function adminNotification($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data,
            'readAt' => $notification->read_at?->toIso8601String(),
            'createdAt' => $notification->created_at?->toIso8601String(),
        ];
    }

    public static function returnRequestSummary($request): array
    {
        return [
            'id' => $request->id,
            'requestNumber' => $request->request_number,
            'status' => $request->status,
            'resolutionType' => $request->resolution_type,
            'orderNumber' => $request->relationLoaded('order') ? $request->order?->order_number : null,
            'createdAt' => $request->created_at?->toIso8601String(),
        ];
    }

    public static function returnRequest($request): array
    {
        return [
            'id' => $request->id,
            'requestNumber' => $request->request_number,
            'status' => $request->status,
            'resolutionType' => $request->resolution_type,
            'adminNote' => $request->admin_note,
            'approvedAt' => $request->approved_at?->toIso8601String(),
            'completedAt' => $request->completed_at?->toIso8601String(),
            'order' => $request->relationLoaded('order') && $request->order
                ? self::orderSummary($request->order)
                : null,
            'items' => $request->relationLoaded('items')
                ? $request->items->map(fn ($item) => [
                    'id' => $item->id,
                    'qty' => $item->qty,
                    'reason' => $item->reason,
                    'description' => $item->description,
                    'productName' => $item->orderItem?->product_name,
                ])->values()->all()
                : [],
            'media' => $request->relationLoaded('media')
                ? $request->media->map(fn ($m) => [
                    'id' => $m->id,
                    'url' => storage_url($m->path),
                    'type' => $m->type,
                ])->values()->all()
                : [],
            'shipment' => $request->relationLoaded('shipment') && $request->shipment
                ? [
                    'courier' => $request->shipment->courier,
                    'trackingNumber' => $request->shipment->tracking_number,
                    'shippedAt' => $request->shipment->shipped_at?->toIso8601String(),
                    'receivedAt' => $request->shipment->received_at?->toIso8601String(),
                ]
                : null,
            'replacementOrder' => $request->relationLoaded('replacementOrder') && $request->replacementOrder
                ? [
                    'id' => $request->replacementOrder->id,
                    'orderNumber' => $request->replacementOrder->order_number,
                    'orderStatus' => $request->replacementOrder->order_status,
                    'courier' => $request->replacementOrder->courier,
                    'courierService' => $request->replacementOrder->courier_service,
                    'trackingNumber' => $request->replacementOrder->tracking_number,
                ]
                : null,
            'createdAt' => $request->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, StockMovement>  $movements
     * @return list<array<string, mixed>>
     */
    public static function stockMovements(Collection $movements): array
    {
        $orderIds = $movements
            ->where('reference_type', Order::class)
            ->pluck('reference_id')
            ->filter()
            ->unique()
            ->values();

        $orderNumbers = $orderIds->isNotEmpty()
            ? Order::whereIn('id', $orderIds)->pluck('order_number', 'id')
            : collect();

        return $movements
            ->map(fn (StockMovement $movement) => self::stockMovement($movement, $orderNumbers))
            ->values()
            ->all();
    }

    public static function stockMovement(StockMovement $movement, ?Collection $orderNumbers = null): array
    {
        $orderNumber = null;
        if ($movement->reference_type === Order::class && $movement->reference_id) {
            $orderNumber = $orderNumbers?->get($movement->reference_id)
                ?? Order::find($movement->reference_id)?->order_number;
        }

        return [
            'id' => $movement->id,
            'type' => $movement->type,
            'quantity' => $movement->quantity,
            'reason' => $movement->reason,
            'referenceType' => $movement->reference_type,
            'referenceId' => $movement->reference_id,
            'orderNumber' => $orderNumber,
            'createdAt' => $movement->created_at?->toIso8601String(),
            'product' => $movement->relationLoaded('product') && $movement->product
                ? ['name' => $movement->product->name]
                : null,
            'warehouse' => $movement->relationLoaded('warehouse') && $movement->warehouse
                ? ['name' => $movement->warehouse->name]
                : null,
        ];
    }
}
