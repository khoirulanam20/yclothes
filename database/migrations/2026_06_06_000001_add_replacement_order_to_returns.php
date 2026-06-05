<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->foreignId('replacement_order_id')->nullable()->after('order_id')->constrained('orders')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_replacement')->default(false)->after('inventory_decremented');
            $table->foreignId('source_return_request_id')->nullable()->after('is_replacement')->constrained('return_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_return_request_id');
            $table->dropColumn('is_replacement');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replacement_order_id');
        });
    }
};
