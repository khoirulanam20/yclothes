<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductRelation;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDuplicateService
{
    public function __construct(
        private ProductVariantService $variantService,
    ) {}

    public function duplicate(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->load(['attributeValues', 'variants', 'relations']);

            $copy = $product->replicate([
                'slug',
                'sku',
                'views',
                'rating_avg',
                'review_count',
            ]);

            $copy->name = 'Salinan dari '.$product->name;
            $copy->slug = $this->uniqueSlug($product->slug);
            $copy->sku = $this->uniqueSku($product->sku ?? 'SKU-'.$product->id);
            $copy->is_active = false;
            $copy->image = $this->copyFile($product->image, 'products');
            $copy->images = $this->copyGallery($product->images);
            $copy->save();

            foreach ($product->attributeValues as $value) {
                ProductAttributeValue::create([
                    'product_id' => $copy->id,
                    'attribute_id' => $value->attribute_id,
                    'value' => $value->value,
                ]);
            }

            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate(['sku']);
                $newVariant->parent_product_id = $copy->id;
                $newVariant->sku = $this->uniqueSku($variant->sku.'-copy');
                $newVariant->image = $variant->image
                    ? $this->copyFile($variant->image, 'products/variants')
                    : null;
                $newVariant->save();
            }

            if ($product->variants->isEmpty() && $product->isConfigurable()) {
                $this->variantService->syncFromProduct($copy->fresh());
            }

            foreach ($product->relations as $relation) {
                ProductRelation::create([
                    'product_id' => $copy->id,
                    'related_product_id' => $relation->related_product_id,
                    'type' => $relation->type,
                ]);
            }

            return $copy->fresh(['attributeValues', 'variants']);
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base.'-copy';
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-copy-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function uniqueSku(string $base): string
    {
        $sku = $base;
        $counter = 1;

        while (Product::where('sku', $sku)->exists() || ProductVariant::where('sku', $sku)->exists()) {
            $sku = $base.'-'.$counter;
            $counter++;
        }

        return $sku;
    }

    private function copyFile(?string $path, string $directory): ?string
    {
        if (! $path || Str::startsWith($path, 'http')) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $newPath = $directory.'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->copy($path, $newPath);

        return $newPath;
    }

    private function copyGallery(?array $images): ?array
    {
        if (! $images) {
            return null;
        }

        return array_values(array_filter(array_map(
            fn ($path) => $this->copyFile($path, 'products/gallery'),
            $images
        )));
    }
}
