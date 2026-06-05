<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('coupon_code', 50)->nullable()->unique();
            $table->unsignedInteger('uses_per_coupon')->default(0);
            $table->unsignedInteger('uses_per_customer')->default(0);
            $table->enum('discount_type', ['percentage', 'fixed', 'free_shipping']);
            $table->decimal('discount_amount', 15, 2);
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->decimal('max_discount', 15, 2)->nullable();
            $table->json('category_ids')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('cart_rule_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('times_used')->default(0);
            $table->timestamps();

            $table->unique(['cart_rule_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_rule_usages');
        Schema::dropIfExists('cart_rules');
    }
};
