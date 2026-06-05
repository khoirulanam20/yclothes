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
            'shippingCity' => $order->shipping_city,
            'shippingCost' => $order->shipping_cost,
            'totalPrice' => $order->total_price,
            'taxAmount' => $order->tax_amount,
            'discountAmount' => $order->discount_amount,
            'grandTotal' => $order->grand_total,
            'paymentMethod' => $order->payment_method,
            'paymentStatus' => $order->payment_status,
            'orderStatus' => $order->order_status,
            'bankName' => $order->bank_name,
            'bankAccountNumber' => $order->bank_account_number,
            'bankAccountName' => $order->bank_account_name,
            'paymentDueAt' => $order->payment_due_at?->toIso8601String(),
            'paidAt' => $order->paid_at?->toIso8601String(),
            'courier' => $order->courier,
            'courierService' => $order->courier_service,
            'trackingNumber' => $order->tracking_number,
            'notes' => $order->notes,
            'createdAt' => $order->created_at?->toIso8601String(),
        ];

        if ($detailed && $order->relationLoaded('items')) {
            $data['items'] = $order->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
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
            'province' => $city->province,
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
            'streetAddress' => $address->street_address,
            'city' => $address->city,
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
            'createdAt' => $order->created_at?->toIso8601String(),
            'itemsCount' => $order->items_count ?? null,
        ];
    }
}
