<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Rumah');
            $table->string('recipient_name', 100);
            $table->string('phone', 20);
            $table->text('street_address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('type', ['shipping', 'billing', 'both'])->default('both');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
