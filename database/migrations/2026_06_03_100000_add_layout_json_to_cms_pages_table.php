<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cms_pages') || Schema::hasColumn('cms_pages', 'layout_json')) {
            return;
        }

        Schema::table('cms_pages', function (Blueprint $table) {
            $table->json('layout_json')->nullable()->after('content');
            $table->string('layout_version', 20)->default('puck-1')->after('layout_json');
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropColumn(['layout_json', 'layout_version']);
        });
    }
};
