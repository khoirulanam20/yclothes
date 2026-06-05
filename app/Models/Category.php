<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'image', 'order'];

    public function getImageUrlAttribute(): string
    {
        if (empty($this->image)) {
            return '';
        }
        if (Str::startsWith($this->image, 'http')) {
            return $this->image;
        }

        return storage_url($this->image) ?? '';
    }

    public static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function descendants(): Collection
    {
        $all = new Collection;

        foreach ($this->children as $child) {
            $all->push($child);
            $all = $all->merge($child->descendants());
        }

        return $all;
    }

    /**
     * @return list<int>
     */
    public function descendantIds(bool $includeSelf = false): array
    {
        $ids = $includeSelf ? [$this->id] : [];

        foreach ($this->descendants() as $descendant) {
            $ids[] = $descendant->id;
        }

        return $ids;
    }

    public static function tree(): Collection
    {
        $all = static::orderBy('order')->get()->keyBy('id');

        foreach ($all as $category) {
            $category->setRelation('children', new Collection);
        }

        $roots = new Collection;

        foreach ($all as $category) {
            if ($category->parent_id && $all->has($category->parent_id)) {
                $all[$category->parent_id]->children->push($category);
            } else {
                $roots->push($category);
            }
        }

        return $roots;
    }
}
