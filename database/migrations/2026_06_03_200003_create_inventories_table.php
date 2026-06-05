<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'product_variant_id'], 'inventories_product_warehouse_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
