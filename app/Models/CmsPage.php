<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'layout_json', 'layout_version', 'banner_image',
        'meta_title', 'meta_description', 'status',
    ];

    protected function casts(): array
    {
        return [
            'layout_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CmsPage $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (empty($this->banner_image)) {
            return null;
        }

        if (Str::startsWith($this->banner_image, 'http')) {
            return $this->banner_image;
        }

        return storage_url($this->banner_image);
    }
}
