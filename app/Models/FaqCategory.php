<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    protected $fillable = ['name', 'sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(FaqItem::class, 'category_id')->orderBy('sort_order');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }
}
