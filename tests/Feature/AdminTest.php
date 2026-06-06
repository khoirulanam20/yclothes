<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertStatus(200);
    }

    public function test_admin_login_success(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@yclothes.test',
            'password' => 'admin123',
        ])->assertRedirect('/admin');
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@yclothes.test',
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors();
    }

    public function test_admin_dashboard_requires_auth(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_accessible_when_authenticated(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertStatus(200);
    }

    public function test_admin_products_list(): void
    {
        $this->actingAs($this->admin)->get('/admin/products')->assertStatus(200);
    }

    public function test_admin_product_create_form(): void
    {
        $this->actingAs($this->admin)->get('/admin/products/create')->assertStatus(200);
    }

    public function test_admin_product_store(): void
    {
        Storage::fake('public');
        $category = Category::first();

        $family = \App\Models\AttributeFamily::where('name', 'Fashion Default')->first();

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'type' => 'simple',
            'attribute_family_id' => $family?->id,
            'sku' => 'ADMIN-TEST-001',
            'name' => 'Produk Test',
        ]);

        $product = Product::where('sku', 'ADMIN-TEST-001')->first();
        $response->assertRedirect("/admin/products/{$product->id}/edit");
        $this->assertDatabaseHas('products', ['name' => 'Produk Test']);
    }

    public function test_admin_product_edit_form(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->admin)->get("/admin/products/{$product->id}/edit");
        $response->assertStatus(200);
        $response->assertSee($product->name);
    }

    public function test_admin_product_update(): void
    {
        $product = Product::first();

        $family = \App\Models\AttributeFamily::where('name', 'Fashion Default')->first();

        $response = $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'category_id' => $product->category_id,
            'attribute_family_id' => $family->id,
            'type' => $product->type->value ?? 'simple',
            'sku' => $product->sku ?? 'UPDATED-SKU',
            'name' => 'Produk Updated',
            'price' => 150000,
            'description' => 'Deskripsi updated',
        ]);

        $response->assertRedirect("/admin/products/{$product->id}/edit");
        $this->assertDatabaseHas('products', ['name' => 'Produk Updated']);
    }

    public function test_admin_product_delete(): void
    {
        $product = Product::first();

        $response = $this->actingAs($this->admin)->delete("/admin/products/{$product->id}");

        $response->assertRedirect('/admin/products');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_settings_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/settings')->assertStatus(200);
    }

    public function test_admin_settings_update(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings', [
            'name' => 'Admin Updated',
            'email' => 'admin@yclothes.test',
        ])->assertRedirect('/admin/settings');

        $this->assertEquals('Admin Updated', $this->admin->fresh()->name);
    }

    public function test_admin_configuration_index(): void
    {
        $this->actingAs($this->admin)->get('/admin/configuration')->assertStatus(200);
    }

    public function test_admin_configuration_edit_and_save(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/configuration/general/store')
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->post('/admin/configuration/general/store', [
                'brand_name' => 'yClothes Updated',
                'store_location' => 'Jakarta',
                'wa_number' => '628123456789',
            ])
            ->assertRedirect('/admin/configuration/general/store');

        $this->assertEquals('yClothes Updated', Setting::where('key', 'brand_name')->value('value'));
    }

    public function test_old_theme_route_redirects_to_configuration(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/theme')
            ->assertRedirect('/admin/configuration/general/design');
    }

    public function test_old_integrations_route_redirects_to_configuration(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/integrations')
            ->assertRedirect('/admin/configuration/general/seo');
    }

    public function test_admin_appearance_redirects_to_configuration(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/appearance')
            ->assertRedirect('/admin/configuration/general/tracking');
    }

    public function test_admin_settings_requires_auth(): void
    {
        $this->get('/admin/settings')->assertRedirect('/admin/login');
    }

    public function test_admin_appearance_requires_auth(): void
    {
        $this->get('/admin/appearance')->assertRedirect('/admin/login');
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@yclothes.test',
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertForbidden();
    }
}
