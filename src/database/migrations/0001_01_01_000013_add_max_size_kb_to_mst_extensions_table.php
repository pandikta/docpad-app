<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_extensions', function (Blueprint $table) {
            $table->unsignedInteger('max_size_kb')->default(1024)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('mst_extensions', function (Blueprint $table) {
            $table->dropColumn('max_size_kb');
        });
    }
};
