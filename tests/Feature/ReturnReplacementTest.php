<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\User;
use App\Services\ReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnReplacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_replacement_resolution_creates_replacement_order(): void
    {
        [$order, $returnRequest] = $this->createReturnReadyForResolve();

        app(ReturnService::class)->resolve($returnRequest, 'replacement');

        $returnRequest->refresh();
        $this->assertEquals('replacing', $returnRequest->status);
        $this->assertNotNull($returnRequest->replacement_order_id);

        $replacement = $returnRequest->replacementOrder;
        $this->assertTrue($replacement->is_replacement);
        $this->assertEquals('processed', $replacement->order_status);
        $this->assertEquals('paid', $replacement->payment_status);
        $this->assertEquals($returnRequest->id, $replacement->source_return_request_id);
        $this->assertCount(1, $replacement->items);
        $this->assertEquals(1, $replacement->items->first()->qty);
    }

    public function test_admin_can_ship_replacement_and_customer_can_complete_flow(): void
    {
        [$order, $returnRequest] = $this->createReturnReadyForResolve();
        $returnService = app(ReturnService::class);
        $returnService->resolve($returnRequest, 'replacement');
        $returnRequest->refresh();

        $admin = User::where('is_admin', true)->first();
        $this->actingAs($admin)->post(route('admin.returns.ship-replacement', $returnRequest), [
            'courier' => 'JNE',
            'courier_service' => 'REG',
            'tracking_number' => 'REP123456',
        ])->assertRedirect();

        $replacement = $returnRequest->fresh()->replacementOrder;
        $this->assertEquals('shipped', $replacement->order_status);
        $this->assertEquals('JNE', $replacement->courier);
        $this->assertEquals('REP123456', $replacement->tracking_number);

        grant_order_access($replacement);
        $customer = Customer::first();
        $this->actingAs($customer, 'customer')
            ->post(route('customer.orders.confirm-received', $replacement));

        $replacement->refresh();
        $returnRequest->refresh();
        $this->assertEquals('completed', $replacement->order_status);
        $this->assertEquals('completed', $returnRequest->status);
    }

    public function test_submit_only_selected_return_items(): void
    {
        $customer = Customer::first();
        $productA = Product::first();
        $productB = Product::skip(1)->first();

        $order = $this->createCompletedOrder($customer, [
            ['product' => $productA, 'qty' => 1],
            ['product' => $productB, 'qty' => 2],
        ]);

        $itemA = $order->items->firstWhere('product_id', $productA->id);
        $itemB = $order->items->firstWhere('product_id', $productB->id);

        $response = $this->actingAs($customer, 'customer')->post(route('customer.returns.store', $order), [
            'items' => [
                [
                    'order_item_id' => $itemA->id,
                    'qty' => 1,
                    'reason' => 'Barang rusak / cacat',
                    'description' => 'Cacat',
                ],
            ],
        ]);

        $response->assertRedirect();
        $returnRequest = ReturnRequest::first();
        $this->assertCount(1, $returnRequest->items);
        $this->assertEquals($itemA->id, $returnRequest->items->first()->order_item_id);
        $this->assertEquals('return', $order->fresh()->order_status);

        $this->assertTrue(app(ReturnService::class)->canReturnItem($order->fresh(), $itemB));
        $this->assertFalse(app(ReturnService::class)->canReturnItem($order->fresh(), $itemA));
    }

    public function test_order_status_returns_to_completed_when_return_rejected(): void
    {
        $customer = Customer::first();
        $order = $this->createCompletedOrder($customer, [
            ['product' => Product::first(), 'qty' => 1],
        ]);

        $returnRequest = ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'pending_review',
        ]);

        ReturnRequestItem::create([
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $order->items->first()->id,
            'qty' => 1,
            'reason' => 'Barang rusak / cacat',
        ]);

        app(ReturnService::class)->syncOrderReturnStatus($order);
        $this->assertEquals('return', $order->fresh()->order_status);

        $admin = User::where('is_admin', true)->first();
        $this->actingAs($admin)->post(route('admin.returns.reject', $returnRequest), [
            'admin_note' => 'Bukti tidak cukup jelas',
        ]);

        $this->assertEquals('completed', $order->fresh()->order_status);
    }

    /**
     * @return array{0: Order, 1: ReturnRequest}
     */
    private function createReturnReadyForResolve(): array
    {
        $customer = Customer::first();
        $product = Product::first();
        $product->update(['is_returnable' => true, 'return_window_days' => 30]);

        $order = $this->createCompletedOrder($customer, [
            ['product' => $product, 'qty' => 1],
        ]);

        $returnRequest = ReturnRequest::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'status' => 'received',
        ]);

        ReturnRequestItem::create([
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $order->items->first()->id,
            'qty' => 1,
            'reason' => 'Barang rusak / cacat',
        ]);

        return [$order, $returnRequest];
    }

    /**
     * @param  list<array{product: Product, qty: int}>  $lines
     */
    private function createCompletedOrder(Customer $customer, array $lines): Order
    {
        $order = Order::createTrusted([
            'order_number' => 'INV-RETURN-'.uniqid(),
            'access_token' => 'test-token-'.uniqid(),
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
            'completed_at' => now(),
        ]);

        foreach ($lines as $line) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $line['product']->id,
                'sku' => 'TEST-SKU',
                'product_name' => $line['product']->name,
                'product_price' => 100000,
                'qty' => $line['qty'],
                'subtotal' => 100000 * $line['qty'],
            ]);
        }

        return $order->fresh(['items']);
    }
}
