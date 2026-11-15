<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doc_files', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('content');
            $table->string('mime_type', 150)->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('doc_files', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'mime_type']);
        });
    }
};
