<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductRelation;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductDuplicateService
{
    public function __construct(
        private ProductVariantService $variantService,
        private ProductImageService $imageService,
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
            $copy->image = $this->imageService->copyPath($product->image, 'products');
            $copy->images = $this->imageService->copyGallery($product->images, 'products/gallery');
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
                $newVariant->images = $this->imageService->copyGallery(
                    $variant->resolved_image_paths,
                    'products/variants/gallery',
                );
                $newVariant->image = $this->imageService->primaryPath($newVariant->images, null);
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
}
