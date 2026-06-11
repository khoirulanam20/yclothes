<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionPopup extends Model
{
    protected $fillable = [
        'cart_rule_id', 'title', 'image', 'button_label', 'button_url',
        'display_duration_seconds', 'start_date', 'end_date',
        'show_on_pages', 'is_active', 'priority',
    ];

    protected function casts(): array
    {
        return [
            'show_on_pages' => 'array',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'display_duration_seconds' => 'integer',
            'priority' => 'integer',
        ];
    }

    public function cartRule(): BelongsTo
    {
        return $this->belongsTo(CartRule::class);
    }

    public function isActiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        return $now->between($this->start_date, $this->end_date);
    }
}
