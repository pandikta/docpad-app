<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mst_teams', 'allow_multiple_leaders')) {
            Schema::table('mst_teams', function (Blueprint $table) {
                $table->boolean('allow_multiple_leaders')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mst_teams', 'allow_multiple_leaders')) {
            Schema::table('mst_teams', function (Blueprint $table) {
                $table->dropColumn('allow_multiple_leaders');
            });
        }
    }
};
