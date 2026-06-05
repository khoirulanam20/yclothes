<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_manage_addresses(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->post('/account/addresses', [
                'label' => 'Rumah',
                'recipient_name' => 'John',
                'phone' => '08123456789',
                'street_address' => 'Jl. Test 1',
                'city' => 'Makassar',
                'province' => 'Sulawesi Selatan',
                'type' => 'both',
                'is_default' => true,
            ])
            ->assertRedirect(route('customer.addresses.index'));

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'city' => 'Makassar',
            'is_default' => true,
        ]);
    }
}

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_customer_can_toggle_wishlist(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::first();

        $this->actingAs($customer, 'customer')
            ->postJson('/account/wishlist/toggle', ['product_id' => $product->id])
            ->assertJson(['success' => true, 'in_wishlist' => true]);

        $this->assertDatabaseHas('wishlists', [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
        ]);
    }
}

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_approve_review(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::first();
        $order = Order::create([
            'order_number' => 'INV-TEST1234',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone ?? '08123456789',
            'customer_email' => $customer->email,
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Makassar',
            'total_price' => 100000,
            'grand_total' => 100000,
            'order_status' => 'completed',
        ]);

        $review = Review::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'rating' => 5,
            'review' => 'Bagus!',
            'is_approved' => false,
            'created_at' => now(),
        ]);

        $admin = \App\Models\User::where('is_admin', true)->first();
        $this->actingAs($admin)
            ->post("/admin/reviews/{$review->id}/approve")
            ->assertRedirect();

        $this->assertTrue($review->fresh()->is_approved);
        $this->assertEquals(5.0, (float) $product->fresh()->rating_avg);
    }
}
