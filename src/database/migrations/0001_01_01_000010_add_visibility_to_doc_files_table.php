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
        Schema::table('doc_files', function (Blueprint $table) {
            $table->string('visibility', 20)->default('publish')->after('name');
            $table->index(['visibility', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doc_files', function (Blueprint $table) {
            $table->dropIndex(['visibility', 'user_id']);
            $table->dropColumn('visibility');
        });
    }
};
