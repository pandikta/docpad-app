<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('mst_users')
                ->nullOnDelete();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::table('doc_files', function (Blueprint $table) {
            $table->foreignId('folder_id')
                ->nullable()
                ->after('user_id')
                ->constrained('doc_folders')
                ->nullOnDelete();
        });

        Schema::create('doc_file_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('doc_files')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('mst_users')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_file_favorites');

        Schema::table('doc_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('doc_folders');
    }
};
