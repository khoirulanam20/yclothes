<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_rules', function (Blueprint $table) {
            $table->unsignedInteger('min_qty')->nullable()->after('min_order_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cart_rules', function (Blueprint $table) {
            $table->dropColumn('min_qty');
        });
    }
};
