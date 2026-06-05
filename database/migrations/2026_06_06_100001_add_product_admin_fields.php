<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 100)->nullable()->unique()->after('type');
            $table->text('short_description')->nullable()->after('description');
            $table->boolean('is_active')->default(true)->after('is_featured');
            $table->timestamp('sale_price_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_price_ends_at')->nullable()->after('sale_price_starts_at');
            $table->string('meta_title')->nullable()->after('warranty_days');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        });

        DB::table('products')->whereNull('sku')->orderBy('id')->get()->each(function ($product) {
            DB::table('products')->where('id', $product->id)->update([
                'sku' => 'SKU-'.$product->id.'-'.substr(md5($product->slug), 0, 6),
                'is_active' => true,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'short_description',
                'is_active',
                'sale_price_starts_at',
                'sale_price_ends_at',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ]);
        });
    }
};
