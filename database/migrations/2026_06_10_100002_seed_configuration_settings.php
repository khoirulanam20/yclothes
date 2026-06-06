<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'promo_bar_enabled' => '1',
            'promo_bar_cta_label' => 'Hubungi WA',
            'tax_included' => '0',
            'low_stock_threshold' => '5',
            'midtrans_active' => '0',
            'midtrans_is_production' => '0',
            'guest_checkout_enabled' => '1',
            'minimum_order_enabled' => '0',
            'minimum_order_amount' => '0',
            'minimum_order_message' => 'Minimum pembelian belum terpenuhi.',
            'unique_payment_amount_enabled' => '1',
            'order_number_prefix' => 'INV-',
            'order_number_length' => '8',
            'max_checkout_retry_attempts' => '3',
            'payment_timeout_hours' => '24',
            'auto_cancel_unpaid_orders' => '1',
            'send_email_on_payment_expired' => '1',
            'auto_cancel_on_payment_fail' => '1',
            'payment_fail_action' => 'cancel_order',
            'log_duplicate_webhooks' => '1',
            'reject_webhook_amount_mismatch' => '1',
            'max_payment_confirmation_attempts' => '3',
            'auto_approve_reviews' => '0',
            'reviews_require_login' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        // settings retained on rollback
    }
};
