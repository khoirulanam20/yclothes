<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_family_attributes', function (Blueprint $table) {
            $table->boolean('is_variant_axis')->default(false)->after('attribute_id');
        });

        DB::table('attribute_family_attributes')
            ->whereIn('attribute_id', function ($query) {
                $query->select('id')
                    ->from('attributes')
                    ->whereIn('code', ['size', 'color']);
            })
            ->update(['is_variant_axis' => true]);
    }

    public function down(): void
    {
        Schema::table('attribute_family_attributes', function (Blueprint $table) {
            $table->dropColumn('is_variant_axis');
        });
    }
};
