<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Review extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'customer_id', 'order_id', 'order_item_id', 'rating', 'review', 'images', 'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_approved' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** @return list<string> */
    public function getImagesUrlAttribute(): array
    {
        return collect($this->images ?? [])
            ->filter()
            ->map(fn (string $path) => $this->pathToUrl($path))
            ->values()
            ->all();
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

    public static function notifyAdminIfPending(self $review, string $productName): void
    {
        if ($review->is_approved) {
            return;
        }

        AdminNotification::notify(
            'review_submitted',
            'Ulasan Baru Menunggu Persetujuan',
            $productName,
            ['review_id' => $review->id, 'product_id' => $review->product_id],
        );
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

    private function pathToUrl(string $path): string
    {
        if (Str::startsWith($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
