<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_provider', 20)->nullable()->after('shipping_method');
            $table->string('courier_service_code', 50)->nullable()->after('courier_service');
            $table->string('shipping_etd', 50)->nullable()->after('courier_service_code');
            $table->string('biteship_order_id', 100)->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_provider',
                'courier_service_code',
                'shipping_etd',
                'biteship_order_id',
            ]);
        });
    }
};
