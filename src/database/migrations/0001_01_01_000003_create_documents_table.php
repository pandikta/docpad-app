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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('doc_file_teams');
        Schema::dropIfExists('doc_logs');
        Schema::dropIfExists('doc_files');
        Schema::enableForeignKeyConstraints();

        Schema::create('doc_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')
                  ->nullable()
                  ->constrained('mst_teams')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('mst_users')
                  ->onDelete('cascade');
            $table->string('name');
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });

        Schema::create('doc_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                  ->constrained('doc_files')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('mst_users')
                  ->onDelete('cascade');
            $table->string('action');
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('doc_file_teams');
        Schema::dropIfExists('doc_logs');
        Schema::dropIfExists('doc_files');
        Schema::enableForeignKeyConstraints();
    }
};
