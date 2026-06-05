<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRejectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_reject_return_with_reason(): void
    {
        $customer = Customer::first();
        $order = $this->createCompletedOrder($customer);
        $returnRequest = ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'pending_review',
        ]);

        $admin = User::where('is_admin', true)->first();

        $response = $this->actingAs($admin)->post(route('admin.returns.reject', $returnRequest), [
            'admin_note' => 'Bukti kerusakan tidak cukup jelas',
        ]);

        $response->assertRedirect();
        $returnRequest->refresh();
        $this->assertEquals('rejected', $returnRequest->status);
        $this->assertEquals('Bukti kerusakan tidak cukup jelas', $returnRequest->admin_note);
    }

    public function test_cannot_reject_return_when_not_pending_review(): void
    {
        $customer = Customer::first();
        $order = $this->createCompletedOrder($customer);
        $returnRequest = ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'awaiting_return_shipment',
        ]);

        $admin = User::where('is_admin', true)->first();

        $response = $this->actingAs($admin)->post(route('admin.returns.reject', $returnRequest), [
            'admin_note' => 'Alasan penolakan yang valid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('awaiting_return_shipment', $returnRequest->fresh()->status);
    }

    private function createCompletedOrder(Customer $customer): Order
    {
        $product = Product::first();

        $order = Order::create([
            'order_number' => 'INV-REJECT-TEST',
            'access_token' => 'test-token',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone ?? '08123456789',
            'customer_email' => $customer->email,
            'shipping_address' => 'Jl. Test',
            'shipping_city' => 'Jakarta',
            'shipping_cost' => 0,
            'total_price' => 100000,
            'grand_total' => 100000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'product_name' => $product->name,
            'product_price' => 100000,
            'qty' => 1,
            'subtotal' => 100000,
        ]);

        return $order;
    }
}
