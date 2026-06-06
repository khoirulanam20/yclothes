<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    protected $fillable = [
        'parent_product_id', 'sku', 'name', 'price', 'stock', 'image', 'images',
        'attributes', 'is_active', 'track_stock', 'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'images' => 'array',
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
        ];
    }

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    /** @return list<string> */
    public function getResolvedImagePathsAttribute(): array
    {
        if (! empty($this->images)) {
            return array_values(array_filter($this->images));
        }

        if (! empty($this->image)) {
            return [$this->image];
        }

        return [];
    }

    public function getImagesUrlAttribute(): array
    {
        $paths = $this->resolved_image_paths;

        if ($paths !== []) {
            return array_values(array_filter(array_map(
                fn (string $path) => $this->pathToUrl($path),
                $paths,
            )));
        }

        $parentImages = $this->parentProduct?->images_url ?? [];
        if ($parentImages !== []) {
            return $parentImages;
        }

        $parentImage = $this->parentProduct?->image_url ?? '';
        if ($parentImage !== '') {
            return [$parentImage];
        }

        return [];
    }

    public function getImageUrlAttribute(): string
    {
        $paths = $this->resolved_image_paths;

        if ($paths !== []) {
            return $this->pathToUrl($paths[0]);
        }

        return $this->parentProduct?->image_url ?? '';
    }

    private function pathToUrl(string $path): string
    {
        if (Str::startsWith($path, 'http')) {
            return $path;
        }

        return storage_url($path) ?? '';
    }

    public function getOwnImagesUrlAttribute(): array
    {
        $paths = $this->resolved_image_paths;

        if ($paths === []) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $path) => $this->pathToUrl($path),
            $paths,
        )));
    }

    public function getFinalPriceAttribute(): int
    {
        return $this->price ?? $this->parentProduct->final_price;
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->inventories()->exists()) {
            return (int) $this->inventories()->sum('stock');
        }

        return $this->stock;
    }
}
