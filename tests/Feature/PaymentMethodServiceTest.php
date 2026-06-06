<?php

namespace Tests\Feature;

use App\Models\PaymentBank;
use App\Models\Setting;
use App\Services\PaymentMethodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_bank_transfer_hidden_when_toggle_off(): void
    {
        Setting::updateOrCreate(['key' => 'payment_bank_transfer_enabled'], ['value' => '0']);
        clear_settings_cache();

        $service = app(PaymentMethodService::class);

        $this->assertFalse($service->isBankTransferAvailable());
        $this->assertNotContains('bank_'.PaymentBank::first()->id, $service->allowedCheckoutValues());
    }

    public function test_qris_available_when_enabled_with_image(): void
    {
        Setting::updateOrCreate(['key' => 'payment_qris_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'qris_image'], ['value' => 'payments/qris-test.png']);
        clear_settings_cache();

        $service = app(PaymentMethodService::class);

        $this->assertTrue($service->isQrisAvailable());
        $this->assertContains('qris', $service->allowedCheckoutValues());
    }

    public function test_midtrans_requires_toggle_and_credentials(): void
    {
        Setting::updateOrCreate(['key' => 'payment_midtrans_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'midtrans_active'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'midtrans_client_key'], ['value' => 'client-key']);
        Setting::updateOrCreate(['key' => 'midtrans_server_key'], ['value' => 'server-key']);
        clear_settings_cache();

        $service = app(PaymentMethodService::class);

        $this->assertTrue($service->isMidtransAvailable());
        $this->assertContains('midtrans', $service->allowedCheckoutValues());

        Setting::updateOrCreate(['key' => 'payment_midtrans_enabled'], ['value' => '0']);
        clear_settings_cache();

        $this->assertFalse(app(PaymentMethodService::class)->isMidtransAvailable());
    }

    public function test_doku_requires_toggle_and_credentials(): void
    {
        Setting::updateOrCreate(['key' => 'payment_doku_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'doku_client_id'], ['value' => 'client-id']);
        Setting::updateOrCreate(['key' => 'doku_secret_key'], ['value' => 'secret-key']);
        clear_settings_cache();

        $service = app(PaymentMethodService::class);

        $this->assertTrue($service->isDokuAvailable());
        $this->assertContains('doku', $service->allowedCheckoutValues());
    }
}
