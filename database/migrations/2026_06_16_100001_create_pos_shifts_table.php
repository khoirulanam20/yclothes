<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_cash')->default(0);
            $table->text('opening_notes')->nullable();
            $table->unsignedInteger('closing_cash')->nullable();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};
