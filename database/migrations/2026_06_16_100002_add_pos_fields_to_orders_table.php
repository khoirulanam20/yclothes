<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_source')->default('web')->after('order_number');
            $table->foreignId('warehouse_id')->nullable()->after('order_source')->constrained()->nullOnDelete();
            $table->foreignId('pos_shift_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->after('pos_shift_id')->constrained('users')->nullOnDelete();

            $table->index('order_source');
            $table->index(['warehouse_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('pos_shift_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn('order_source');
        });
    }
};
