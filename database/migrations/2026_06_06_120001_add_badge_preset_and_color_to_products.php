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
            $table->string('badge_preset', 20)->nullable()->after('badge');
            $table->string('badge_color', 7)->nullable()->after('badge_preset');
        });

        $presets = [
            'SALE' => ['preset' => 'sale', 'color' => '#DC2626', 'label' => 'Sale'],
            'NEW' => ['preset' => 'new', 'color' => '#16A34A', 'label' => 'New'],
            'HOT' => ['preset' => 'hot', 'color' => '#EA580C', 'label' => 'Hot'],
            'BEST' => ['preset' => 'hot', 'color' => '#EA580C', 'label' => 'Best'],
        ];

        DB::table('products')->whereNotNull('badge')->where('badge', '!=', '')->orderBy('id')->get()->each(function ($product) use ($presets) {
            $key = strtoupper(trim($product->badge));
            $mapped = $presets[$key] ?? null;

            if ($mapped) {
                DB::table('products')->where('id', $product->id)->update([
                    'badge_preset' => $mapped['preset'],
                    'badge_color' => $mapped['color'],
                    'badge' => $mapped['label'],
                ]);
            } else {
                DB::table('products')->where('id', $product->id)->update([
                    'badge_preset' => 'custom',
                    'badge_color' => '#6366F1',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['badge_preset', 'badge_color']);
        });
    }
};
