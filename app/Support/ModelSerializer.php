<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Slider;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CmsLayoutService;
use App\Services\HtmlSanitizer;
use App\Services\InventoryService;
use App\Services\ProductRelationService;
use App\Support\Serializers\OrderSerializer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ModelSerializer
{
    public static function product(Product $product, bool $detailed = false): array
    {
        $inventory = app(InventoryService::class);

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => HtmlSanitizer::normalizeStorageUrls($product->description),
            'price' => $product->price,
            'salePrice' => $product->sale_price,
            'finalPrice' => $product->final_price,
            'catalogUnitPrice' => $product->getAttribute('catalog_unit_price'),
            'catalogHasDiscount' => (bool) $product->getAttribute('catalog_has_discount'),
            'imageUrl' => $product->image_url,
            'badge' => $product->badge_label,
            'badgeLabel' => $product->badge_label,
            'badgeColor' => $product->badge_color,
            'discountPercentage' => $product->discount_percentage,
            'isOutOfStock' => $inventory->isOutOfStock($product),
            'isPurchasable' => $inventory->canOrder($product, null, 1),
            'category' => $product->relationLoaded('category') && $product->category
                ? self::category($product->category)
                : null,
            'type' => $product->type?->value ?? $product->type,
        ];

        if ($detailed) {
            $data['imagesUrl'] = $product->images_url;
            $data['sizes'] = $product->sizes;
            $data['colors'] = $product->colors;
            $data['shortDescription'] = $product->short_description;
            $data['weight'] = $product->weight;
            $data['weightLabel'] = format_product_weight($product->weight);
            $data['minPurchaseQty'] = 1;
            $data['ratingAvg'] = (float) $product->rating_avg;
            $data['reviewCount'] = $product->review_count;
            $data['trackStock'] = $product->track_stock;
            $data['variants'] = $product->relationLoaded('activeVariants')
                ? $product->activeVariants->map(fn ($v) => self::variant($v, $product))->values()->all()
                : [];
        }

        return $data;
    }

    public static function variant(ProductVariant $variant, ?Product $product = null): array
    {
        $inventoryService = app(InventoryService::class);
        $stock = $variant->stock;
        if ($product) {
            $stock = $inventoryService->getAvailableStock($product, $variant);
        }

        $data = [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $variant->price,
            'stock' => $stock,
            'finalPrice' => $variant->final_price,
            'imageUrl' => $variant->image_url,
            'imagesUrl' => $variant->images_url,
            'ownImagesUrl' => $variant->own_images_url,
            'attributes' => $variant->attributes,
            'isActive' => $variant->is_active,
        ];

        if ($product) {
            $data['isPurchasable'] = $inventoryService->canOrder($product, $variant, 1);
            $data['isOutOfStock'] = $inventoryService->isOutOfStock($product, $variant);
            $data['trackStock'] = $variant->track_stock || $product->track_stock;
        }

        return $data;
    }

    public static function adminVariant(Product $product, ProductVariant $variant): array
    {
        $inventoryService = app(InventoryService::class);

        return array_merge(self::variant($variant, $product), [
            'imagesPaths' => $variant->resolved_image_paths,
            'inventories' => $inventoryService->inventoryRowsFor($product, $variant->id),
        ]);
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
            'subtitle' => $slider->subtitle,
            'imageUrl' => $slider->image_url,
            'linkUrl' => $slider->link_url,
            'ctaLabel' => $slider->cta_label,
            'sortOrder' => $slider->sort_order,
            'isActive' => (bool) $slider->is_active,
        ];
    }

    public static function adminProduct(Product $product): array
    {
        $product->loadMissing(['variants', 'relations.relatedProduct']);
        $relationService = app(ProductRelationService::class);

        return array_merge(self::product($product, true), [
            'sku' => $product->sku,
            'shortDescription' => $product->short_description,
            'categoryId' => $product->category_id,
            'attributeFamilyId' => $product->attribute_family_id,
            'isFeatured' => (bool) $product->is_featured,
            'isActive' => (bool) $product->is_active,
            'trackStock' => (bool) $product->track_stock,
            'allowBackorder' => (bool) $product->allow_backorder,
            'isReturnable' => (bool) $product->is_returnable,
            'returnWindowDays' => $product->return_window_days,
            'warrantyDays' => $product->warranty_days,
            'weight' => $product->weight,
            'salePriceStartsAt' => $product->sale_price_starts_at?->toIso8601String(),
            'salePriceEndsAt' => $product->sale_price_ends_at?->toIso8601String(),
            'metaTitle' => $product->meta_title,
            'metaDescription' => $product->meta_description,
            'metaKeywords' => $product->meta_keywords,
            'imagesPaths' => $product->images ?? [],
            'badgePreset' => $product->badge_preset?->value ?? 'none',
            'variants' => $product->variants->map(fn ($v) => self::adminVariant($product, $v))->values()->all(),
            'relatedProductIds' => $product->relations->where('type', ProductRelationService::TYPE_RELATED)->pluck('related_product_id')->values()->all(),
            'upSellProductIds' => $product->relations->where('type', ProductRelationService::TYPE_UP_SELL)->pluck('related_product_id')->values()->all(),
            'crossSellProductIds' => $product->relations->where('type', ProductRelationService::TYPE_CROSS_SELL)->pluck('related_product_id')->values()->all(),
            'relatedProducts' => $relationService->summariesForAdmin($product, ProductRelationService::TYPE_RELATED),
            'upSellProducts' => $relationService->summariesForAdmin($product, ProductRelationService::TYPE_UP_SELL),
            'crossSellProducts' => $relationService->summariesForAdmin($product, ProductRelationService::TYPE_CROSS_SELL),
        ]);
    }

    public static function adminProductListItem(Product $product): array
    {
        return array_merge(self::product($product), [
            'sku' => $product->sku,
            'isActive' => (bool) $product->is_active,
            'category' => $product->relationLoaded('category') && $product->category
                ? ['name' => $product->category->name]
                : null,
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
        $layoutService = app(CmsLayoutService::class);

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'layoutJson' => $layoutService->normalizeLayoutForRender($page->layout_json),
            'layoutVersion' => $page->layout_version,
            'bannerUrl' => $page->banner_url,
            'metaTitle' => $page->meta_title,
            'metaDescription' => $page->meta_description,
            'status' => $page->status,
            'hasLayout' => $layoutService->hasRenderableContent($page->layout_json),
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
            'customerName' => $review->customer?->name ?? 'Pembeli',
            'createdAt' => $review->created_at?->toIso8601String(),
            'isApproved' => (bool) $review->is_approved,
            'imagesUrl' => $review->images_url,
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
        return OrderSerializer::order($order, $detailed);
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
        return OrderSerializer::orderSummary($order);
    }

    public static function orderStatusHistory($history): array
    {
        return OrderSerializer::orderStatusHistory($history);
    }

    public static function paymentConfirmation($confirmation): array
    {
        return OrderSerializer::paymentConfirmation($confirmation);
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
            'variant' => $movement->relationLoaded('variant') && $movement->variant
                ? [
                    'sku' => $movement->variant->sku,
                    'label' => self::variantLabel($movement->variant),
                ]
                : null,
            'displayName' => self::movementDisplayName($movement),
            'warehouse' => $movement->relationLoaded('warehouse') && $movement->warehouse
                ? ['name' => $movement->warehouse->name]
                : null,
        ];
    }

    public static function movementDisplayName(StockMovement $movement): string
    {
        $productName = $movement->product?->name ?? '—';
        $variant = $movement->relationLoaded('variant') ? $movement->variant : null;

        if (! $variant) {
            return $productName;
        }

        $label = self::variantLabel($variant);

        return $label ? "{$productName} — {$label}" : $productName;
    }

    public static function variantLabel(ProductVariant $variant): ?string
    {
        $attributes = $variant->attributes ?? [];
        $parts = array_filter([
            $attributes['size'] ?? null,
            $attributes['color'] ?? null,
        ]);

        if ($parts !== []) {
            return implode(' / ', $parts);
        }

        return $variant->name ?: null;
    }
}
