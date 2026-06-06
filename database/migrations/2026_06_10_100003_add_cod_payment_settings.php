<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'payment_cod_enabled' => '0',
            'cod_instructions' => 'Bayar tunai saat kurir mengantar pesanan. Pastikan nominal sesuai total pesanan.',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', ['payment_cod_enabled', 'cod_instructions'])->delete();
    }
};
