<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_rules', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('meta_title')->nullable()->after('slug');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('banner_image')->nullable()->after('meta_description');
        });

        Schema::table('catalog_rules', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('meta_title')->nullable()->after('slug');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('banner_image')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('cart_rules', function (Blueprint $table) {
            $table->dropColumn(['slug', 'meta_title', 'meta_description', 'banner_image']);
        });

        Schema::table('catalog_rules', function (Blueprint $table) {
            $table->dropColumn(['slug', 'meta_title', 'meta_description', 'banner_image']);
        });
    }
};
