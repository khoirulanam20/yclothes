<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->nullable();
            $table->string('provider')->default('midtrans');
            $table->string('event_type')->nullable();
            $table->string('transaction_status')->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['order_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
