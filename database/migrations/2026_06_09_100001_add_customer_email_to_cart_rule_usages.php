<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_rule_usages', function (Blueprint $table) {
            if (! Schema::hasColumn('cart_rule_usages', 'customer_email')) {
                $table->string('customer_email', 255)->nullable()->after('customer_id');
                $table->index(['cart_rule_id', 'customer_email']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_rule_usages', function (Blueprint $table) {
            if (Schema::hasColumn('cart_rule_usages', 'customer_email')) {
                $table->dropIndex(['cart_rule_id', 'customer_email']);
                $table->dropColumn('customer_email');
            }
        });
    }
};
