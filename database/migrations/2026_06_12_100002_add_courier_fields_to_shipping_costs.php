<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_costs', function (Blueprint $table) {
            $table->string('courier_code', 30)->nullable()->after('city_name');
            $table->string('courier_name', 100)->nullable()->after('courier_code');
            $table->string('province_code', 10)->nullable()->after('courier_name');
        });

        Schema::table('shipping_costs', function (Blueprint $table) {
            $table->unique(['courier_code', 'regency_code'], 'shipping_costs_courier_regency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_costs', function (Blueprint $table) {
            $table->dropUnique('shipping_costs_courier_regency_unique');
            $table->dropColumn(['courier_code', 'courier_name', 'province_code']);
        });
    }
};
