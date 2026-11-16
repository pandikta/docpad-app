<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mst_users', 'email_notifications_enabled')) {
            Schema::table('mst_users', function (Blueprint $table) {
                $table->boolean('email_notifications_enabled')->default(true)->after('storage_limit_mb');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mst_users', 'email_notifications_enabled')) {
            Schema::table('mst_users', function (Blueprint $table) {
                $table->dropColumn('email_notifications_enabled');
            });
        }
    }
};
