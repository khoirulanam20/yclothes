<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2);
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rate_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_zones');
        Schema::dropIfExists('tax_rate_categories');
        Schema::dropIfExists('tax_rates');
    }
};
