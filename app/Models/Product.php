<?php

namespace App\Models;

use App\Enums\BadgePreset;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'attribute_family_id', 'type', 'sku', 'name', 'slug',
        'description', 'short_description', 'price', 'sale_price',
        'sale_price_starts_at', 'sale_price_ends_at',
        'image', 'images', 'sizes', 'colors', 'badge', 'badge_preset', 'badge_color', 'weight',
        'is_featured', 'is_active', 'track_stock', 'allow_backorder',
        'is_returnable', 'return_window_days', 'warranty_days',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'type' => ProductType::class,
            'badge_preset' => BadgePreset::class,
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'is_returnable' => 'boolean',
            'return_window_days' => 'integer',
            'warranty_days' => 'integer',
            'price' => 'integer',
            'sale_price' => 'integer',
            'sale_price_starts_at' => 'datetime',
            'sale_price_ends_at' => 'datetime',
            'views' => 'integer',
            'weight' => 'integer',
            'rating_avg' => 'decimal:2',
            'review_count' => 'integer',
        ];
    }

    public function getColorsAttribute($value): ?array
    {
        $fromEav = $this->eavDecodedValue('color');
        if ($fromEav !== null) {
            return $fromEav;
        }

        if ($value === null || $value === 'null') {
            return null;
        }

        $colors = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($colors)) {
            return null;
        }

        return array_map(fn ($c) => is_string($c) ? ['hex' => $c, 'name' => $c] : $c, $colors);
    }

    public function getSizesAttribute($value): ?array
    {
        $fromEav = $this->eavDecodedValue('size');
        if ($fromEav !== null) {
            return is_array($fromEav) ? array_values($fromEav) : null;
        }

        if ($value === null || $value === 'null') {
            return null;
        }

        return is_string($value) ? json_decode($value, true) : $value;
    }

    public function setSizesAttribute($value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->attributes['sizes'] = null;

            return;
        }

        $this->attributes['sizes'] = is_string($value) ? $value : json_encode(array_values($value));
    }

    private function eavDecodedValue(string $code): mixed
    {
        if (! $this->relationLoaded('attributeValues')) {
            if (! $this->exists || ! $this->attributeValues()->exists()) {
                return null;
            }
            $this->load(['attributeValues.attribute']);
        }

        $pav = $this->attributeValues->first(fn ($v) => $v->attribute?->code === $code);

        return $pav ? $pav->decodedValue() : null;
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
            if (empty($product->sku)) {
                $product->sku = 'SKU-'.strtoupper(Str::random(8));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attributeFamily(): BelongsTo
    {
        return $this->belongsTo(AttributeFamily::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function displayableAttributes(): HasMany
    {
        return $this->attributeValues()->with('attribute')->whereHas('attribute');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getDisplayPriceAttribute(): int
    {
        return $this->attributes['catalog_unit_price'] ?? $this->final_price;
    }

    public function getHasCatalogDiscountAttribute(): bool
    {
        return (bool) ($this->attributes['catalog_has_discount'] ?? false);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'parent_product_id');
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function relations()
    {
        return $this->hasMany(ProductRelation::class);
    }

    public function relatedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot('type')
            ->withTimestamps();
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }

    public function isConfigurable(): bool
    {
        return $this->type === ProductType::Configurable;
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->inventories()->whereNull('product_variant_id')->exists()) {
            return (int) $this->inventories()->whereNull('product_variant_id')->sum('stock');
        }

        return 0;
    }

    public function getImageUrlAttribute(): string
    {
        $path = $this->image;
        if (empty($path)) {
            $path = collect($this->images ?? [])->filter()->first() ?? '';
        }

        if (empty($path)) {
            return '';
        }
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return storage_url($path) ?? '';
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

            return storage_url($path) ?? '';
        }, $this->images);
    }

    public function getFinalPriceAttribute(): int
    {
        if ($this->sale_price && $this->isSalePriceActive()) {
            return $this->sale_price;
        }

        return $this->price;
    }

    public function isSalePriceActive(): bool
    {
        if (! $this->sale_price) {
            return false;
        }

        $now = now();

        if ($this->sale_price_starts_at && $now->lt($this->sale_price_starts_at)) {
            return false;
        }

        if ($this->sale_price_ends_at && $now->gt($this->sale_price_ends_at)) {
            return false;
        }

        return true;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->isSalePriceActive() && $this->price > 0) {
            return (int) round((1 - $this->sale_price / $this->price) * 100);
        }

        return null;
    }

    public function getBadgeLabelAttribute(): ?string
    {
        $preset = $this->badge_preset;

        if (! $preset || $preset === BadgePreset::None) {
            return null;
        }

        if ($preset === BadgePreset::Custom) {
            return $this->badge ?: null;
        }

        return $this->badge ?: $preset->defaultLabel();
    }

    public function getBadgeColorAttribute($value): ?string
    {
        if ($value) {
            return $value;
        }

        $preset = $this->badge_preset;
        if (! $preset || $preset === BadgePreset::None) {
            return null;
        }

        return $preset->defaultColor();
    }
}
