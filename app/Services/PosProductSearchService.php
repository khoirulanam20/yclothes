<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PosProductSearchService
{
    public function search(
        ?string $query,
        ?string $sku,
        ?int $categoryId,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $builder = Product::query()
            ->where('is_active', true)
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when($sku, function (Builder $q) use ($sku) {
                $q->where(function (Builder $inner) use ($sku) {
                    $inner->where('sku', $sku)
                        ->orWhereHas('variants', fn (Builder $variantQuery) => $variantQuery
                            ->where('sku', $sku)
                            ->where('is_active', true));
                });
            })
            ->when($query, function (Builder $q) use ($query) {
                $q->where(function (Builder $inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhereHas('variants', fn (Builder $variantQuery) => $variantQuery
                            ->where('is_active', true)
                            ->where(function (Builder $variantInner) use ($query) {
                                $variantInner->where('sku', 'like', "%{$query}%")
                                    ->orWhere('name', 'like', "%{$query}%");
                            }));
                });
            })
            ->orderBy('name');

        return $builder->paginate($perPage);
    }

    public function findActiveProduct(int $productId): ?Product
    {
        return Product::query()
            ->where('is_active', true)
            ->with(['activeVariants'])
            ->find($productId);
    }

    public function findBySku(string $sku): Product|ProductVariant|null
    {
        $variant = ProductVariant::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->whereHas('parentProduct', fn (Builder $q) => $q->where('is_active', true))
            ->with('parentProduct.activeVariants')
            ->first();

        if ($variant) {
            return $variant;
        }

        return Product::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->with(['activeVariants'])
            ->first();
    }
}
