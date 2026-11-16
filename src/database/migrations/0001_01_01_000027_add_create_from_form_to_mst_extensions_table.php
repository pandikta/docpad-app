<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mst_extensions', 'create_from_form')) {
            Schema::table('mst_extensions', function (Blueprint $table) {
                $table->boolean('create_from_form')->default(false)->after('is_active');
            });

            DB::table('mst_extensions')
                ->whereIn('extension', ['txt', 'md', 'json', 'csv', 'html', 'xml', 'yaml', 'yml', 'log', 'sql'])
                ->update(['create_from_form' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mst_extensions', 'create_from_form')) {
            Schema::table('mst_extensions', function (Blueprint $table) {
                $table->dropColumn('create_from_form');
            });
        }
    }
};
