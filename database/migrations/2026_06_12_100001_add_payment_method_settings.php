<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_confirmations', function (Blueprint $table) {
            $table->foreignId('payment_bank_id')->nullable()->change();
        });

        $defaults = [
            'payment_bank_transfer_enabled' => '1',
            'payment_qris_enabled' => '0',
            'payment_midtrans_enabled' => '0',
            'payment_doku_enabled' => '0',
            'qris_instructions' => 'Scan QRIS di bawah, bayar sesuai nominal, lalu konfirmasi pembayaran.',
            'doku_is_production' => '0',
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
