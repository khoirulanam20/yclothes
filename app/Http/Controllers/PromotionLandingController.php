<?php

namespace App\Http\Controllers;

use App\Models\CartRule;
use App\Models\CatalogRule;
use App\Support\ModelSerializer;
use Inertia\Inertia;

class PromotionLandingController extends Controller
{
    public function show(string $slug)
    {
        $cartRule = CartRule::where('slug', $slug)->where('is_active', true)->first();
        if ($cartRule && $cartRule->isActiveNow()) {
            return $this->renderLanding([
                'type' => 'cart',
                'name' => $cartRule->name,
                'description' => $cartRule->description,
                'metaTitle' => $cartRule->meta_title ?: $cartRule->name,
                'metaDescription' => $cartRule->meta_description,
                'bannerImageUrl' => storage_url($cartRule->banner_image),
                'couponCode' => $cartRule->coupon_code,
                'discountType' => $cartRule->discount_type,
                'discountAmount' => (float) $cartRule->discount_amount,
            ]);
        }

        $catalogRule = CatalogRule::where('slug', $slug)->where('is_active', true)->first();
        if ($catalogRule && $catalogRule->isActiveNow()) {
            return $this->renderLanding([
                'type' => 'catalog',
                'name' => $catalogRule->name,
                'description' => $catalogRule->description,
                'metaTitle' => $catalogRule->meta_title ?: $catalogRule->name,
                'metaDescription' => $catalogRule->meta_description,
                'bannerImageUrl' => storage_url($catalogRule->banner_image),
                'ruleType' => $catalogRule->rule_type,
                'discountAmount' => (float) $catalogRule->discount_amount,
            ]);
        }

        abort(404);
    }

    /** @param  array<string, mixed>  $promotion */
    private function renderLanding(array $promotion)
    {
        return Inertia::render('Guest/Promotions/Show', [
            'promotion' => $promotion,
        ]);
    }
}
