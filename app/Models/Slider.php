<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slider extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image', 'link_url', 'cta_label', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): string
    {
        if (Str::startsWith($this->image, 'http')) {
            return $this->image;
        }

        return storage_url($this->image);
    }
}
