<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_users', function (Blueprint $table) {
            $table->unsignedInteger('storage_limit_mb')->nullable()->default(100)->after('is_superadmin');
        });
    }

    public function down(): void
    {
        Schema::table('mst_users', function (Blueprint $table) {
            $table->dropColumn('storage_limit_mb');
        });
    }
};
