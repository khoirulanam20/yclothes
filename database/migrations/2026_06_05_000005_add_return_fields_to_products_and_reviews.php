<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_returnable')->default(true)->after('allow_backorder');
            $table->unsignedInteger('return_window_days')->nullable()->after('is_returnable');
            $table->unsignedInteger('warranty_days')->nullable()->after('return_window_days');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_item_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_returnable', 'return_window_days', 'warranty_days']);
        });
    }
};
