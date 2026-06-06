<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'email_admin_recipients' => '',
            'email_admin_new_order' => '1',
            'email_admin_payment_submitted' => '1',
            'email_customer_order_created' => '1',
            'email_customer_invoice_on_created' => '0',
            'email_customer_invoice_on_paid' => '1',
            'email_customer_status_awaiting_verification' => '1',
            'email_customer_status_confirmed' => '1',
            'email_customer_status_processed' => '1',
            'email_customer_status_shipped' => '1',
            'email_customer_status_delivered' => '1',
            'email_customer_status_completed' => '1',
            'email_customer_status_cancelled' => '1',
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
