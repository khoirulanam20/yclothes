<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductRelation;
use Illuminate\Support\Collection;

class ProductRelationService
{
    public const TYPE_RELATED = 'related';

    public const TYPE_UP_SELL = 'up_sell';

    public const TYPE_CROSS_SELL = 'cross_sell';

    public function syncAll(Product $product, array $related, array $upSell, array $crossSell): void
    {
        $this->syncByType($product, self::TYPE_RELATED, $related);
        $this->syncByType($product, self::TYPE_UP_SELL, $upSell);
        $this->syncByType($product, self::TYPE_CROSS_SELL, $crossSell);
    }

    public function syncByType(Product $product, string $type, array $relatedIds): void
    {
        $product->relations()->where('type', $type)->delete();

        foreach (array_unique($relatedIds) as $relatedId) {
            $relatedId = (int) $relatedId;
            if ($relatedId <= 0 || $relatedId === $product->id) {
                continue;
            }

            ProductRelation::create([
                'product_id' => $product->id,
                'related_product_id' => $relatedId,
                'type' => $type,
            ]);
        }
    }

    /** @return Collection<int, Product> */
    public function resolveForStorefront(Product $product, string $type, int $limit = 4): Collection
    {
        $inventoryService = app(InventoryService::class);

        return ProductRelation::query()
            ->where('product_id', $product->id)
            ->where('type', $type)
            ->with('relatedProduct')
            ->get()
            ->pluck('relatedProduct')
            ->filter(fn (?Product $related) => $related
                && $related->is_active
                && $inventoryService->canOrder($related, null, 1))
            ->take($limit)
            ->values();
    }

    /** @param  list<int>  $productIds
     * @return Collection<int, Product>
     */
    public function resolveCrossSellForCart(array $productIds, int $limit = 4): Collection
    {
        if ($productIds === []) {
            return collect();
        }

        $inventoryService = app(InventoryService::class);

        $relatedIds = ProductRelation::query()
            ->whereIn('product_id', $productIds)
            ->where('type', self::TYPE_CROSS_SELL)
            ->pluck('related_product_id')
            ->unique()
            ->reject(fn (int $id) => in_array($id, $productIds, true))
            ->values();

        if ($relatedIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $relatedIds)
            ->where('is_active', true)
            ->get()
            ->filter(fn (Product $product) => $inventoryService->canOrder($product, null, 1))
            ->take($limit)
            ->values();
    }

    /** @return list<array{id: int, name: string, sku: string, imageUrl: string, price: int}> */
    public function summariesForAdmin(Product $product, string $type): array
    {
        return $product->relations
            ->where('type', $type)
            ->map(function (ProductRelation $relation) {
                $related = $relation->relatedProduct;
                if (! $related) {
                    return null;
                }

                return [
                    'id' => $related->id,
                    'name' => $related->name,
                    'sku' => $related->sku,
                    'imageUrl' => $related->image_url,
                    'price' => $related->final_price,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
