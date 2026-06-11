<?php

namespace App\Services;

use App\Models\CartRule;
use App\Models\PromotionPopup;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PromotionPopupService
{
    public function __construct(private StorageCopyService $storageCopy) {}
    /** @var list<string> */
    public const PAGE_OPTIONS = [
        'all' => 'Semua Halaman',
        'home' => 'Beranda',
        'products' => 'Daftar Produk',
        'product_detail' => 'Detail Produk',
        'cart' => 'Keranjang',
        'checkout' => 'Checkout',
        'blog' => 'Blog',
    ];

    public function resolveForRoute(?string $routeName): ?PromotionPopup
    {
        if (! $routeName) {
            return null;
        }

        $pageKey = $this->routeToPageKey($routeName);
        if (! $pageKey) {
            return null;
        }

        return PromotionPopup::query()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderByDesc('priority')
            ->get()
            ->first(fn (PromotionPopup $popup) => $this->matchesPage($popup, $pageKey));
    }

    public function routeToPageKey(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'home' => 'home',
            $routeName === 'products.index' => 'products',
            $routeName === 'products.show' => 'product_detail',
            $routeName === 'cart.index' => 'cart',
            str_starts_with($routeName, 'checkout.') => 'checkout',
            str_starts_with($routeName, 'blog.') => 'blog',
            default => null,
        };
    }

    private function matchesPage(PromotionPopup $popup, string $pageKey): bool
    {
        $pages = $popup->show_on_pages ?? [];

        return in_array('all', $pages, true) || in_array($pageKey, $pages, true);
    }

    public function syncFromCartRule(CartRule $cartRule): PromotionPopup
    {
        if (empty($cartRule->banner_image)) {
            throw new RuntimeException('Upload banner landing terlebih dahulu sebelum sinkronisasi.');
        }

        $popup = PromotionPopup::firstOrNew(['cart_rule_id' => $cartRule->id]);
        $oldImage = $popup->image;

        $popup->fill([
            'title' => $cartRule->name,
            'image' => $this->storageCopy->copyPublicFile($cartRule->banner_image, 'promotion-popups'),
            'button_label' => 'Lihat Promo',
            'button_url' => $cartRule->slug ? '/promo/'.$cartRule->slug : null,
            'display_duration_seconds' => 0,
            'start_date' => $cartRule->start_date->copy()->startOfDay(),
            'end_date' => $cartRule->end_date->copy()->endOfDay(),
            'show_on_pages' => ['all'],
            'is_active' => (bool) $cartRule->is_active,
            'priority' => $cartRule->priority ?? 0,
        ]);

        $popup->save();

        if ($oldImage && $oldImage !== $popup->image) {
            Storage::disk('public')->delete($oldImage);
        }

        return $popup;
    }

    public function serialize(?PromotionPopup $popup): ?array
    {
        if (! $popup) {
            return null;
        }

        return [
            'id' => $popup->id,
            'title' => $popup->title,
            'imageUrl' => storage_url($popup->image),
            'buttonLabel' => $popup->button_label,
            'buttonUrl' => $popup->button_url,
            'displayDurationSeconds' => $popup->display_duration_seconds,
        ];
    }
}
