<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->text('shipping_address');
            $table->string('shipping_city');
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('total_price');
            $table->unsignedInteger('grand_total');
            $table->string('payment_method')->default('bank_transfer');
            $table->string('payment_status')->default('pending');
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('order_status')->default('pending');
            $table->string('courier')->nullable();
            $table->string('courier_service')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
