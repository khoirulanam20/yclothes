<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'customer_id', 'order_id', 'rating', 'review', 'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function recalculateProductRating(int $productId): void
    {
        $stats = static::where('product_id', $productId)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Product::where('id', $productId)->update([
            'rating_avg' => round($stats->avg_rating ?? 0, 2),
            'review_count' => $stats->total ?? 0,
        ]);
    }
}
