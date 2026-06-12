<?php

namespace Tests\Feature;

use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\ShippingCost;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cart_index_returns_success(): void
    {
        $response = $this->get('/cart');
        $response->assertStatus(200);
    }

    public function test_cart_add_product(): void
    {
        $product = Product::first();

        $response = $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_cart_add_then_update_qty(): void
    {
        $product = Product::first();

        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $itemKey = $product->id.'--';

        $response = $this->postJson('/cart/update', [
            'key' => $itemKey,
            'qty' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_cart_add_then_remove(): void
    {
        $product = Product::first();

        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $itemKey = $product->id.'--';

        $response = $this->postJson('/cart/remove', [
            'key' => $itemKey,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_checkout_page_accessible(): void
    {
        $product = Product::first();
        $this->postJson('/cart/add', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);

        $response = $this->get('/checkout');
        $response->assertStatus(200);
    }

    public function test_checkout_empty_cart_redirects(): void
    {
        $response = $this->get('/checkout');
        $response->assertRedirect('/cart');
    }

    public function test_buy_now_checkouts_only_selected_item(): void
    {
        $products = Product::take(2)->get();
        $first = $products[0];
        $second = $products[1];

        $this->postJson('/cart/add', ['product_id' => $first->id, 'qty' => 1]);
        $secondKey = $second->id.'--';

        $response = $this->postJson('/cart/add', [
            'product_id' => $second->id,
            'qty' => 1,
            'buy_now' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => route('checkout.index'),
            ]);

        $this->assertSame([$secondKey], session(CartService::CHECKOUT_SELECTION_KEY));

        $checkout = $this->get('/checkout');
        $checkout->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Checkout/Index')
                ->has('items', 1)
                ->where('items.0.productName', $second->name)
            );
    }

    public function test_cart_index_shows_all_items_when_checkout_selection_is_stale(): void
    {
        $products = Product::take(2)->get();
        $first = $products[0];
        $second = $products[1];
        $firstKey = $first->id.'--';
        $secondKey = $second->id.'--';

        $this->postJson('/cart/add', ['product_id' => $first->id, 'qty' => 1]);
        $this->postJson('/cart/add', ['product_id' => $second->id, 'qty' => 1]);

        session([CartService::CHECKOUT_SELECTION_KEY => [$firstKey]]);

        $this->postJson('/cart/remove', ['key' => $firstKey]);

        $this->get('/cart')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Guest/Cart/Index')
                ->has('items', 1)
                ->where('items.0.product.slug', $second->slug)
            );
    }

    public function test_checkout_process_removes_only_selected_items_from_cart(): void
    {
        $products = Product::take(2)->get();
        $first = $products[0];
        $second = $products[1];
        $firstKey = $first->id.'--';
        $secondKey = $second->id.'--';

        $this->postJson('/cart/add', ['product_id' => $first->id, 'qty' => 1]);
        $this->postJson('/cart/add', ['product_id' => $second->id, 'qty' => 1]);

        $this->post('/cart/checkout-selection', ['keys' => [$firstKey]])
            ->assertRedirect(route('checkout.index'));

        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'test@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'courier_code' => $shipping->courier_code ?? 'jne',
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()))
            ->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_name' => $first->name,
            'qty' => 1,
        ]);
        $this->assertDatabaseMissing('order_items', [
            'product_name' => $second->name,
        ]);

        $cart = session(CartService::SESSION_KEY, []);
        $this->assertArrayHasKey($secondKey, $cart);
        $this->assertArrayNotHasKey($firstKey, $cart);
        $this->assertNull(session(CartService::CHECKOUT_SELECTION_KEY));
    }
}
