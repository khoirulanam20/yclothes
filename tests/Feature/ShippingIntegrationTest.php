<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\ShippingCost;
use App\Models\User;
use App\Support\WilayahCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_admin_can_save_shipping_configuration(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/configuration/sales/shipping/settings', [
                'shipping_mode' => 'biteship',
                'biteship_api_key' => 'biteship_test.key',
                'biteship_sandbox_mode' => '1',
                'biteship_fulfillment' => 'rates_only',
                'biteship_origin_postal_code' => '10110',
                'biteship_active_couriers' => 'jne,sicepat',
            ])
            ->assertRedirect('/admin/configuration/sales/shipping/settings');

        $this->assertEquals('biteship', Setting::where('key', 'shipping_mode')->value('value'));
        $this->assertEquals('biteship_test.key', Setting::where('key', 'biteship_api_key')->value('value'));
    }

    public function test_admin_can_filter_shipping_costs(): void
    {
        ShippingCost::query()->delete();

        ShippingCost::create([
            'courier_code' => 'jne',
            'courier_name' => 'JNE',
            'province_code' => '33',
            'province_name' => 'Jawa Tengah',
            'regency_code' => '33.73',
            'regency_name' => 'Kabupaten Temanggung',
            'city_name' => 'Kabupaten Temanggung',
            'cost' => 15000,
            'is_active' => true,
        ]);

        ShippingCost::create([
            'courier_code' => 'jnt',
            'courier_name' => 'J&T Express',
            'province_code' => '31',
            'province_name' => 'DKI Jakarta',
            'regency_code' => '31.71',
            'regency_name' => 'Kota Jakarta Pusat',
            'city_name' => 'Kota Jakarta Pusat',
            'cost' => 12000,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/shipping-costs?courier_code=jne&status=active')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ShippingCosts/Index')
                ->has('costs.data', 1)
                ->where('costs.data.0.courierCode', 'jne')
                ->where('filters.courier_code', 'jne')
                ->where('filters.status', 'active')
            );

        $this->actingAs($this->admin)
            ->get('/admin/shipping-costs?search=Jakarta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('costs.data', 1)
                ->where('costs.data.0.regencyName', 'Kota Jakarta Pusat')
            );
    }

    public function test_admin_can_bulk_update_shipping_cost_status(): void
    {
        $active = ShippingCost::where('is_active', true)->firstOrFail();
        $inactive = ShippingCost::updateOrCreate(
            [
                'courier_code' => 'lion',
                'regency_code' => '99.99',
            ],
            [
                'courier_name' => 'Lion Parcel',
                'province_code' => '99',
                'province_name' => 'Test Provinsi',
                'regency_name' => 'Test Kota',
                'city_name' => 'Test Kota',
                'cost' => 20000,
                'is_active' => false,
            ],
        );

        $this->actingAs($this->admin)
            ->post('/admin/shipping-costs/bulk', [
                'action' => 'deactivate',
                'ids' => [$active->id],
            ])
            ->assertRedirect();

        $this->assertFalse($active->fresh()->is_active);

        $this->actingAs($this->admin)
            ->post('/admin/shipping-costs/bulk', [
                'action' => 'activate',
                'ids' => [$inactive->id],
            ])
            ->assertRedirect();

        $this->assertTrue($inactive->fresh()->is_active);
    }

    public function test_admin_can_bulk_delete_shipping_costs(): void
    {
        $cost = ShippingCost::updateOrCreate(
            [
                'courier_code' => 'lion',
                'regency_code' => '98.88',
            ],
            [
                'courier_name' => 'Lion Parcel',
                'province_code' => '98',
                'province_name' => 'Test Provinsi Hapus',
                'regency_name' => 'Test Kota Hapus',
                'city_name' => 'Test Kota Hapus',
                'cost' => 20000,
                'is_active' => true,
            ],
        );

        $this->actingAs($this->admin)
            ->post('/admin/shipping-costs/bulk', [
                'action' => 'delete',
                'ids' => [$cost->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('shipping_costs', ['id' => $cost->id]);
    }

    public function test_admin_can_create_manual_shipping_cost(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/shipping-costs', [
                'courier_code' => 'jnt',
                'province_code' => '33',
                'province_name' => 'Jawa Tengah',
                'regency_code' => '33.22',
                'regency_name' => 'Kabupaten Semarang',
                'cost' => 18000,
                'cost_per_kg' => 4000,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.shipping-costs.index'));

        $this->assertDatabaseHas('shipping_costs', [
            'courier_code' => 'jnt',
            'regency_code' => '33.22',
            'cost' => 18000,
        ]);
    }

    public function test_shipping_options_manual_mode_returns_couriers_for_regency(): void
    {
        $product = \App\Models\Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->postJson('/checkout/shipping-options', [
            'regency_code' => '33.73',
            'postal_code' => '56211',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['options', 'subtotal'])
            ->assertJsonFragment(['courierCode' => 'jne']);
    }

    public function test_checkout_process_with_biteship_service_selection(): void
    {
        Setting::updateOrCreate(['key' => 'shipping_mode'], ['value' => 'biteship']);
        Setting::updateOrCreate(['key' => 'biteship_api_key'], ['value' => 'biteship_test.key']);
        Setting::updateOrCreate(['key' => 'biteship_origin_postal_code'], ['value' => '10110']);
        Setting::updateOrCreate(['key' => 'biteship_active_couriers'], ['value' => 'jne']);
        clear_settings_cache();

        Http::fake([
            'api.biteship.com/*' => Http::response([
                'pricing' => [
                    [
                        'courier_code' => 'jne',
                        'courier_name' => 'JNE',
                        'courier_service_code' => 'yes',
                        'courier_service_name' => 'YES',
                        'price' => 25000,
                        'shipment_duration_range' => '1 - 2',
                    ],
                ],
            ], 200),
        ]);

        $product = \App\Models\Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $bank = \App\Models\PaymentBank::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => 'jne',
            'courier_service_code' => 'yes',
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'test@example.com',
            'courier' => 'JNE',
            'courier_service_code' => 'yes',
            'shipping_provider' => 'biteship',
            'shipping_cost' => 25000,
        ]);
    }

    public function test_checkout_process_with_courier_code(): void
    {
        $product = \App\Models\Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $bank = \App\Models\PaymentBank::first();

        $response = $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => 'jne',
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_email' => 'test@example.com',
            'courier' => 'JNE',
            'shipping_provider' => 'manual',
            'shipping_method' => 'manual',
        ]);
    }

    public function test_biteship_shipping_options_use_api(): void
    {
        Setting::updateOrCreate(['key' => 'shipping_mode'], ['value' => 'biteship']);
        Setting::updateOrCreate(['key' => 'biteship_api_key'], ['value' => 'biteship_test.key']);
        Setting::updateOrCreate(['key' => 'biteship_origin_postal_code'], ['value' => '10110']);
        Setting::updateOrCreate(['key' => 'biteship_active_couriers'], ['value' => 'jne']);
        clear_settings_cache();

        Http::fake([
            'api.biteship.com/*' => Http::response([
                'pricing' => [
                    [
                        'courier_code' => 'jne',
                        'courier_name' => 'JNE',
                        'courier_service_code' => 'reg',
                        'courier_service_name' => 'REG',
                        'price' => 18000,
                        'shipment_duration_range' => '2 - 3',
                    ],
                    [
                        'courier_code' => 'jne',
                        'courier_name' => 'JNE',
                        'courier_service_code' => 'yes',
                        'courier_service_name' => 'YES',
                        'price' => 25000,
                        'shipment_duration_range' => '1 - 2',
                    ],
                ],
            ], 200),
        ]);

        $product = \App\Models\Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $response = $this->postJson('/checkout/shipping-options', [
            'regency_code' => '33.73',
            'postal_code' => '56211',
        ]);

        $response->assertOk()
            ->assertJsonPath('options.0.courierCode', 'jne')
            ->assertJsonPath('options.0.courierServiceCode', 'reg')
            ->assertJsonPath('options.0.cost', 18000)
            ->assertJsonPath('options.1.courierServiceCode', 'yes');
    }

    public function test_wilayah_code_normalizes_legacy_format(): void
    {
        $this->assertSame('33.73', WilayahCode::normalize('3373'));
        $this->assertSame('33', WilayahCode::normalize('33'));
        $this->assertTrue(WilayahCode::equals('3373', '33.73'));
    }

    public function test_biteship_webhook_updates_order(): void
    {
        $order = \App\Models\Order::createTrusted([
            'order_number' => 'INV-BS-WH',
            'customer_name' => 'Test',
            'customer_phone' => '08123456789',
            'customer_email' => 'wh@example.com',
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Temanggung',
            'total_price' => 100000,
            'grand_total' => 115000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'order_status' => 'processed',
            'shipping_provider' => 'biteship',
            'biteship_order_id' => 'bs-123',
            'courier' => 'JNE',
        ]);

        $response = $this->postJson('/webhooks/biteship', [
            'order_id' => 'bs-123',
            'status' => 'shipped',
            'courier' => [
                'waybill_id' => 'JNE123456',
            ],
        ]);

        $response->assertOk();
        $order->refresh();
        $this->assertSame('JNE123456', $order->tracking_number);
        $this->assertSame('shipped', $order->order_status);
    }
}
