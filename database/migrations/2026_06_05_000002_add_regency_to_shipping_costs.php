<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_costs', function (Blueprint $table) {
            $table->string('regency_code', 10)->nullable()->after('city_name');
            $table->string('regency_name', 100)->nullable()->after('regency_code');
            $table->string('province_name', 100)->nullable()->after('regency_name');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_costs', function (Blueprint $table) {
            $table->dropColumn(['regency_code', 'regency_name', 'province_name']);
        });
    }
};
