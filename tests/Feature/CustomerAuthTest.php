<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register(): void
    {
        Notification::fake();
        $response = $this->post('/account/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('customer.verification.notice'));
        $this->assertDatabaseHas('customers', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
        $this->assertAuthenticatedAs(Customer::first(), 'customer');
    }

    public function test_customer_can_login(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/account/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_customer_can_logout(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->post('/account/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest('customer');
    }

    public function test_unverified_customer_cannot_checkout(): void
    {
        $this->seed();
        $customer = Customer::factory()->unverified()->create();

        $product = Product::first();
        $this->actingAs($customer, 'customer')
            ->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $this->actingAs($customer, 'customer')
            ->get('/checkout')
            ->assertRedirect(route('customer.verification.notice'));
    }
}

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $this->actingAs($customer, 'customer')
            ->put('/account/profile', [
                'name' => 'New Name',
                'email' => $customer->email,
                'phone' => '08987654321',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
            'phone' => '08987654321',
        ]);
    }
}

class CheckoutCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_checkout_links_order_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::first();

        $this->actingAs($customer, 'customer')
            ->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->actingAs($customer, 'customer')
            ->post('/checkout/process', array_merge([
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'shipping_address' => 'Jl. Test No. 1',
                'shipping_city' => $shipping->id,
                'payment_method' => 'bank_'.$bank->id,
            ], $this->checkoutWilayahFields()))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
    }

    public function test_guest_checkout_still_works(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Guest User',
            'customer_phone' => '08123456789',
            'customer_email' => 'guest@example.com',
            'shipping_address' => 'Jl. Guest No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()))->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => null,
            'customer_email' => 'guest@example.com',
        ]);
    }
}
