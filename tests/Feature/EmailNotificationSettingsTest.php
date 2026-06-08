<?php

namespace Tests\Feature;

use App\Enums\InvoiceEmailContext;
use App\Mail\AdminNewOrderMail;
use App\Mail\OrderCreatedMail;
use App\Mail\OrderInvoiceMail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\PaymentBank;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCost;
use App\Models\User;
use App\Services\InvoicePdfService;
use App\Services\OrderPaymentService;
use App\Services\OrderWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@yclothes.test')->first();
    }

    public function test_admin_can_save_email_notification_settings(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.configuration.update', ['slug' => 'general/email_notifications']), [
                'email_admin_recipients' => 'ops@shop.test, finance@shop.test',
                'email_admin_new_order' => true,
                'email_admin_payment_submitted' => true,
                'email_customer_order_created' => true,
                'email_customer_invoice_on_created' => false,
                'email_customer_invoice_on_paid' => true,
                'send_email_on_payment_expired' => true,
                'email_customer_status_shipped' => false,
            ])
            ->assertRedirect('/admin/configuration/general/email_notifications');

        $this->assertDatabaseHas('settings', ['key' => 'email_admin_recipients', 'value' => 'ops@shop.test, finance@shop.test']);
        $this->assertDatabaseHas('settings', ['key' => 'email_customer_status_shipped', 'value' => '0']);
    }

    public function test_admin_notification_requires_recipients_when_enabled(): void
    {
        $this->actingAs($this->admin)
            ->from('/admin/configuration/general/email_notifications')
            ->post(route('admin.configuration.update', ['slug' => 'general/email_notifications']), [
                'email_admin_recipients' => '',
                'email_admin_new_order' => true,
                'email_admin_payment_submitted' => false,
            ])
            ->assertRedirect('/admin/configuration/general/email_notifications')
            ->assertSessionHasErrors('email_admin_recipients');
    }

    public function test_order_created_mail_respects_toggle(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'email_customer_order_created'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_admin_new_order'], ['value' => '0']);
        clear_settings_cache();

        $this->placeOrder();

        Mail::assertNotQueued(OrderCreatedMail::class);
    }

    public function test_invoice_mail_on_created_and_on_paid_are_separate(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'email_customer_order_created'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_customer_invoice_on_created'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'email_customer_invoice_on_paid'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_admin_new_order'], ['value' => '0']);
        clear_settings_cache();

        $order = $this->placeOrder();

        Mail::assertQueued(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) {
            return $mail->context === InvoiceEmailContext::Created;
        });
        Mail::assertNotQueued(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) {
            return $mail->context === InvoiceEmailContext::Paid;
        });

        app(OrderPaymentService::class)->markPaid($order->fresh(), 'admin');

        Mail::assertNotQueued(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) {
            return $mail->context === InvoiceEmailContext::Paid;
        });
    }

    public function test_invoice_mail_on_paid_when_enabled(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'email_customer_order_created'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_customer_invoice_on_created'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_customer_invoice_on_paid'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'email_admin_new_order'], ['value' => '0']);
        clear_settings_cache();

        $order = $this->placeOrder();

        app(OrderPaymentService::class)->markPaid($order->fresh(), 'admin');

        Mail::assertQueued(OrderInvoiceMail::class, function (OrderInvoiceMail $mail) {
            return $mail->context === InvoiceEmailContext::Paid;
        });
    }

    public function test_admin_mail_only_goes_to_custom_recipients(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'email_admin_recipients'], ['value' => 'ops@shop.test']);
        Setting::updateOrCreate(['key' => 'email_admin_new_order'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'email_customer_order_created'], ['value' => '0']);
        clear_settings_cache();

        $this->placeOrder();

        Mail::assertQueued(AdminNewOrderMail::class, function (AdminNewOrderMail $mail) {
            return $mail->hasTo('ops@shop.test');
        });

        Mail::assertQueued(AdminNewOrderMail::class, function (AdminNewOrderMail $mail) {
            return ! $mail->hasTo($this->admin->email);
        });
    }

    public function test_status_email_respects_per_status_toggle(): void
    {
        Mail::fake();

        Setting::updateOrCreate(['key' => 'email_customer_status_shipped'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'email_customer_status_confirmed'], ['value' => '1']);
        clear_settings_cache();

        $order = $this->placeOrder();
        $workflow = app(OrderWorkflowService::class);

        $workflow->transition($order->fresh(), 'awaiting_verification', 'Bukti bayar', 'customer');
        $workflow->transition($order->fresh(), 'confirmed', 'Dikonfirmasi', 'admin');
        $workflow->transition($order->fresh(), 'processed', 'Diproses', 'admin');
        $workflow->transition($order->fresh(), 'shipped', 'Dikirim', 'admin');

        Mail::assertQueued(OrderStatusMail::class, function (OrderStatusMail $mail) {
            return $mail->toStatus === 'confirmed';
        });

        Mail::assertNotQueued(OrderStatusMail::class, function (OrderStatusMail $mail) {
            return $mail->toStatus === 'shipped';
        });
    }

    public function test_invoice_email_includes_product_line_items(): void
    {
        $order = $this->placeOrder()->load('items');
        $productName = $order->items->first()->product_name;

        $html = (new OrderInvoiceMail($order, InvoiceEmailContext::Paid))->render();

        $this->assertStringContainsString($productName, $html);
        $this->assertStringContainsString('Grand Total', $html);
        $this->assertStringContainsString('Faktur PDF Terlampir', $html);
    }

    public function test_invoice_email_attaches_pdf(): void
    {
        $order = $this->placeOrder()->load('items');

        $mail = new OrderInvoiceMail($order, InvoiceEmailContext::Paid);
        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
        $this->assertSame('faktur-'.$order->order_number.'.pdf', $attachments[0]->as);
        $this->assertSame('application/pdf', $attachments[0]->mime);

        $pdf = app(InvoicePdfService::class)->generate($order, 'Faktur Pembayaran #'.$order->order_number);

        $this->assertNotEmpty($pdf);
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    private function placeOrder(): Order
    {
        $product = Product::first();
        $shipping = ShippingCost::first();
        $bank = PaymentBank::first();

        $this->postJson('/cart/add', ['product_id' => $product->id, 'qty' => 1]);

        $this->post('/checkout/process', array_merge([
            'customer_name' => 'Test User',
            'customer_phone' => '08123456789',
            'customer_email' => 'email-settings@example.com',
            'shipping_address' => 'Jl. Test No. 1',
            'shipping_city' => $shipping->id,
            'payment_method' => 'bank_'.$bank->id,
        ], $this->checkoutWilayahFields()));

        return Order::where('customer_email', 'email-settings@example.com')->firstOrFail();
    }
}
