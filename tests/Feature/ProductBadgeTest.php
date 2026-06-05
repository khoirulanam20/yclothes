<?php

namespace Tests\Feature;

use App\Enums\BadgePreset;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_admin_can_save_sale_badge_with_custom_color(): void
    {
        $admin = User::where('is_admin', true)->first();
        $product = Product::first();
        $category = Category::first();

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'attribute_family_id' => $product->attribute_family_id,
            'type' => $product->type->value,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price ?: 100000,
            'badge_preset' => BadgePreset::Sale->value,
            'badge' => 'Sale',
            'badge_color' => '#FF0000',
            'image' => UploadedFile::fake()->image('product.jpg'),
        ]);

        $response->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame(BadgePreset::Sale, $product->badge_preset);
        $this->assertSame('#FF0000', $product->badge_color);
        $this->assertSame('Sale', $product->badge);
    }

    public function test_storefront_product_payload_includes_badge_color(): void
    {
        $product = Product::first();
        $product->update([
            'badge_preset' => BadgePreset::New,
            'badge' => 'New',
            'badge_color' => '#123456',
            'is_active' => true,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Guest/Products/Show')
            ->where('product.badge', 'New')
            ->where('product.badgeColor', '#123456'));
    }
}
