<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('trx_team_requests', 'company_email')) {
            Schema::table('trx_team_requests', function (Blueprint $table) {
                $table->string('company_email')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trx_team_requests', 'company_email')) {
            Schema::table('trx_team_requests', function (Blueprint $table) {
                $table->dropColumn('company_email');
            });
        }
    }
};
