<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'sale_price',
        'image', 'images', 'sizes', 'colors', 'badge', 'weight',
        'is_featured', 'views',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'sizes' => 'array',
            'is_featured' => 'boolean',
            'price' => 'integer',
            'sale_price' => 'integer',
            'views' => 'integer',
            'weight' => 'integer',
        ];
    }

    public function getColorsAttribute($value): ?array
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        $colors = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($colors)) {
            return null;
        }

        return array_map(fn ($c) => is_string($c) ? ['hex' => $c, 'name' => $c] : $c, $colors);
    }

    public function setColorsAttribute($value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->attributes['colors'] = null;
            return;
        }

        $normalized = array_map(function ($c) {
            if (is_string($c)) {
                return ['hex' => $c, 'name' => $c];
            }
            return ['hex' => $c['hex'] ?? '', 'name' => $c['name'] ?? $c['hex'] ?? ''];
        }, is_string($value) ? json_decode($value, true) : $value);

        $this->attributes['colors'] = json_encode($normalized);
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return '';
        }
        if (Str::startsWith($this->image, 'http')) {
            return $this->image;
        }

        return Storage::disk('public')->url($this->image);
    }

    public function getImagesUrlAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($path) {
            if (Str::startsWith($path, 'http')) {
                return $path;
            }

            return Storage::disk('public')->url($path);
        }, $this->images);
    }

    public function getFinalPriceAttribute(): int
    {
        return $this->sale_price ?: $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->sale_price && $this->price > 0) {
            return (int) round((1 - $this->sale_price / $this->price) * 100);
        }

        return null;
    }
}
