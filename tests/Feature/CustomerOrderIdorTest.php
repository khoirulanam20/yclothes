<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();

        $order = Order::factory()->forCustomer($owner)->create();

        $this->actingAs($intruder, 'customer')
            ->get(route('customer.orders.show', $order))
            ->assertForbidden();
    }

    public function test_customer_cannot_confirm_payment_for_another_customers_order(): void
    {
        $owner = Customer::factory()->create();
        $intruder = Customer::factory()->create();

        $order = Order::factory()->forCustomer($owner)->create();

        $this->actingAs($intruder, 'customer')
            ->post(route('customer.orders.confirm-payment', $order), [])
            ->assertForbidden();
    }
}
