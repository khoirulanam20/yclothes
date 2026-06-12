<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_held_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('items');
            $table->string('coupon_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('held_at');
            $table->timestamps();

            $table->index(['user_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_held_carts');
    }
};
