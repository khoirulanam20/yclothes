<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'order_number_mode' => 'random',
            'order_number_suffix' => '',
            'order_number_start' => '1',
            'out_of_stock_behavior' => 'show_label',
            'weight_unit' => 'gram',
            'mail_enabled' => '0',
            'mail_mailer' => 'smtp',
            'mail_encryption' => 'tls',
            'gdpr_enabled' => '0',
            'gdpr_cookie_lifetime_days' => '365',
            'sitemap_enabled' => '1',
            'sitemap_include_products' => '1',
            'sitemap_include_categories' => '1',
            'sitemap_include_cms' => '1',
            'newsletter_opt_in_enabled' => '0',
            'newsletter_opt_in_label' => 'Berlangganan newsletter untuk promo & update',
            'invoice_show_tax_breakdown' => '0',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        //
    }
};
