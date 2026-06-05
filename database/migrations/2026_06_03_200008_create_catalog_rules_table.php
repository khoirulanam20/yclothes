<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('rule_type', [
                'percentage_discount',
                'fixed_discount',
                'free_shipping_threshold',
                'tiered_qty_discount',
                'buy_x_get_y',
            ]);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('min_order_amount', 15, 2)->nullable();
            $table->unsignedInteger('min_qty')->nullable();
            $table->unsignedInteger('buy_qty')->nullable();
            $table->unsignedInteger('get_qty')->nullable();
            $table->decimal('get_discount_percent', 5, 2)->nullable();
            $table->json('category_ids')->nullable();
            $table->json('product_ids')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_rules');
    }
};
