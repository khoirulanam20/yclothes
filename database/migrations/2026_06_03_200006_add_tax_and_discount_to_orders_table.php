<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('tax_amount')->default(0)->after('total_price');
            $table->unsignedInteger('discount_amount')->default(0)->after('tax_amount');
            $table->string('coupon_code', 50)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_amount', 'discount_amount', 'coupon_code']);
        });
    }
};
