<?php

namespace Tests\Feature;

use App\Models\CartRule;
use App\Models\PromotionPopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CartRulePopupSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_sync_cart_rule_to_promotion_popup(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $bannerPath = 'promotions/banner.jpg';
        Storage::disk('public')->put($bannerPath, 'fake-banner');

        $rule = CartRule::create([
            'name' => 'Promo Natal',
            'slug' => 'promo-natal',
            'coupon_code' => 'NATAL25',
            'discount_type' => 'percentage',
            'discount_amount' => 25,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'priority' => 5,
            'banner_image' => $bannerPath,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cart-rules.sync-popup', $rule))
            ->assertRedirect(route('admin.cart-rules.edit', $rule))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('promotion_popups', [
            'cart_rule_id' => $rule->id,
            'title' => 'Promo Natal',
            'button_label' => 'Lihat Promo',
            'button_url' => '/promo/promo-natal',
            'is_active' => true,
            'priority' => 5,
        ]);

        $popup = PromotionPopup::where('cart_rule_id', $rule->id)->first();
        $this->assertNotNull($popup);
        $this->assertSame(['all'], $popup->show_on_pages);
        $this->assertTrue(Storage::disk('public')->exists($popup->image));
    }

    public function test_resync_updates_existing_popup_without_duplicate(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $bannerPath = 'promotions/banner.jpg';
        Storage::disk('public')->put($bannerPath, 'fake-banner');

        $rule = CartRule::create([
            'name' => 'Promo Awal',
            'slug' => 'promo-awal',
            'discount_type' => 'fixed',
            'discount_amount' => 50000,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'banner_image' => $bannerPath,
        ]);

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));

        $rule->update(['name' => 'Promo Diperbarui']);

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));

        $this->assertSame(1, PromotionPopup::where('cart_rule_id', $rule->id)->count());
        $this->assertDatabaseHas('promotion_popups', [
            'cart_rule_id' => $rule->id,
            'title' => 'Promo Diperbarui',
        ]);
    }

    public function test_sync_without_banner_returns_error(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();

        $rule = CartRule::create([
            'name' => 'Tanpa Banner',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cart-rules.sync-popup', $rule))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('promotion_popups', [
            'cart_rule_id' => $rule->id,
        ]);
    }

    public function test_resync_deletes_previous_popup_image(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $bannerPath = 'promotions/banner.jpg';
        Storage::disk('public')->put($bannerPath, 'fake-banner');

        $rule = CartRule::create([
            'name' => 'Promo Image',
            'slug' => 'promo-image',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'banner_image' => $bannerPath,
        ]);

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));
        $firstImage = PromotionPopup::where('cart_rule_id', $rule->id)->value('image');

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));
        $secondImage = PromotionPopup::where('cart_rule_id', $rule->id)->value('image');

        $this->assertNotSame($firstImage, $secondImage);
        $this->assertFalse(Storage::disk('public')->exists($firstImage));
        $this->assertTrue(Storage::disk('public')->exists($secondImage));
    }

    public function test_deleting_cart_rule_nullifies_linked_popup_reference(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $bannerPath = 'promotions/banner.jpg';
        Storage::disk('public')->put($bannerPath, 'fake-banner');

        $rule = CartRule::create([
            'name' => 'Promo Hapus',
            'slug' => 'promo-hapus',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'banner_image' => $bannerPath,
        ]);

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));
        $popupId = PromotionPopup::where('cart_rule_id', $rule->id)->value('id');

        $this->actingAs($admin)->delete(route('admin.cart-rules.destroy', $rule));

        $this->assertDatabaseHas('promotion_popups', [
            'id' => $popupId,
            'cart_rule_id' => null,
        ]);
    }

    public function test_synced_popup_shared_on_homepage(): void
    {
        $admin = User::where('email', 'admin@yclothes.test')->first();
        $bannerPath = 'promotions/banner.jpg';
        Storage::disk('public')->put($bannerPath, 'fake-banner');

        $rule = CartRule::create([
            'name' => 'Popup dari Cart Rule',
            'slug' => 'popup-cart-rule',
            'discount_type' => 'percentage',
            'discount_amount' => 15,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'banner_image' => $bannerPath,
        ]);

        $this->actingAs($admin)->post(route('admin.cart-rules.sync-popup', $rule));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('promotionPopup.title', 'Popup dari Cart Rule')
            );
    }
}
