<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_families', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->enum('type', [
                'text', 'textarea', 'select', 'multiselect', 'boolean', 'decimal', 'price',
            ]);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->string('validation', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_family_attributes', function (Blueprint $table) {
            $table->foreignId('attribute_family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->primary(['attribute_family_id', 'attribute_id']);
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->unique(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_family_attributes');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_families');
    }
};
