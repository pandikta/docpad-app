<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mst_menus', function (Blueprint $table) {
            $table->string('section', 30)->default('settings')->after('group');
            $table->string('icon', 80)->nullable()->default('pi pi-circle')->after('route_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_menus', function (Blueprint $table) {
            $table->dropColumn(['section', 'icon']);
        });
    }
};
