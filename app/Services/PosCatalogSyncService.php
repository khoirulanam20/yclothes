<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PosCatalogSyncService
{
    public function __construct(private InventoryService $inventoryService) {}

    /**
     * @return array{products: list<array<string, mixed>>, meta: array<string, int|string|null>}
     */
    public function syncPage(
        int $warehouseId,
        int $page = 1,
        int $perPage = 100,
        ?string $updatedSince = null,
    ): array {
        $warehouse = Warehouse::query()
            ->where('id', $warehouseId)
            ->where('is_active', true)
            ->first();

        if (! $warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Gudang tidak ditemukan atau tidak aktif.',
            ]);
        }

        $since = $updatedSince ? Carbon::parse($updatedSince) : null;

        /** @var LengthAwarePaginator<int, Product> $paginator */
        $paginator = Product::query()
            ->where('is_active', true)
            ->when($since, fn ($query) => $query->where('updated_at', '>=', $since))
            ->with(['activeVariants'])
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $products = $paginator->getCollection()
            ->map(fn (Product $product) => $this->serializeProduct($product, $warehouseId))
            ->values()
            ->all();

        return [
            'products' => $products,
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'syncedAt' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProduct(Product $product, int $warehouseId): array
    {
        $variants = $product->activeVariants->map(
            fn (ProductVariant $variant) => $this->serializeVariant($variant, $product, $warehouseId),
        )->values()->all();

        return [
            'id' => $product->id,
            'categoryId' => $product->category_id,
            'type' => $product->type?->value ?? $product->type,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => (int) $product->price,
            'salePrice' => $product->sale_price !== null ? (int) $product->sale_price : null,
            'finalPrice' => (int) $product->final_price,
            'imageUrl' => $product->image_url,
            'trackStock' => $product->track_stock,
            'stock' => $this->inventoryService->getAvailableStockAtWarehouse($product, null, $warehouseId),
            'updatedAt' => $product->updated_at?->toIso8601String(),
            'variants' => $variants,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeVariant(ProductVariant $variant, Product $product, int $warehouseId): array
    {
        $attrs = $variant->attributes ?? [];

        return [
            'id' => $variant->id,
            'parentProductId' => $variant->parent_product_id,
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $variant->price !== null ? (int) $variant->price : null,
            'finalPrice' => (int) $variant->final_price,
            'size' => $attrs['size'] ?? null,
            'color' => $attrs['color'] ?? null,
            'imageUrl' => $variant->image_url,
            'trackStock' => $variant->track_stock,
            'stock' => $this->inventoryService->getAvailableStockAtWarehouse($product, $variant, $warehouseId),
            'updatedAt' => $variant->updated_at?->toIso8601String(),
        ];
    }
}
