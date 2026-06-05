<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('province_code', 10)->nullable()->after('street_address');
            $table->string('province_name', 100)->nullable()->after('province_code');
            $table->string('regency_code', 10)->nullable()->after('province_name');
            $table->string('regency_name', 100)->nullable()->after('regency_code');
            $table->string('district_code', 10)->nullable()->after('regency_name');
            $table->string('district_name', 100)->nullable()->after('district_code');
            $table->string('village_code', 20)->nullable()->after('district_name');
            $table->string('village_name', 100)->nullable()->after('village_code');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('province_code', 10)->nullable()->after('shipping_address');
            $table->string('province_name', 100)->nullable()->after('province_code');
            $table->string('regency_code', 10)->nullable()->after('province_name');
            $table->string('regency_name', 100)->nullable()->after('regency_code');
            $table->string('district_code', 10)->nullable()->after('regency_name');
            $table->string('district_name', 100)->nullable()->after('district_code');
            $table->string('village_code', 20)->nullable()->after('district_name');
            $table->string('village_name', 100)->nullable()->after('village_code');
            $table->string('postal_code', 10)->nullable()->after('village_name');
            $table->string('shipping_method', 50)->default('manual')->after('shipping_cost');
            $table->unsignedBigInteger('unique_payment_amount')->nullable()->after('grand_total');
            $table->string('payment_confirmation_status', 30)->default('none')->after('payment_status');
            $table->timestamp('delivered_at')->nullable()->after('paid_at');
            $table->timestamp('completed_at')->nullable()->after('delivered_at');
            $table->string('refund_status', 30)->nullable()->after('completed_at');
            $table->unsignedBigInteger('refunded_amount')->default(0)->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'province_code', 'province_name', 'regency_code', 'regency_name',
                'district_code', 'district_name', 'village_code', 'village_name',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'province_code', 'province_name', 'regency_code', 'regency_name',
                'district_code', 'district_name', 'village_code', 'village_name',
                'postal_code', 'shipping_method', 'unique_payment_amount',
                'payment_confirmation_status', 'delivered_at', 'completed_at',
                'refund_status', 'refunded_amount',
            ]);
        });
    }
};
