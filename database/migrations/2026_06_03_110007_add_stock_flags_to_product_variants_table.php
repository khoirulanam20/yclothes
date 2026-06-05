<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'track_stock')) {
                $table->boolean('track_stock')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('product_variants', 'allow_backorder')) {
                $table->boolean('allow_backorder')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'allow_backorder']);
        });
    }
};
