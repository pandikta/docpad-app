<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doc_file_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                  ->constrained('doc_files')
                  ->onDelete('cascade');
            $table->foreignId('team_id')
                  ->constrained('mst_teams')
                  ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['document_id', 'team_id']);
            $table->index(['team_id', 'document_id']);
        });

        // Pindahkan relasi team_id lama dari doc_files (jika ada) ke tabel pivot doc_file_teams
        if (Schema::hasColumn('doc_files', 'team_id')) {
            $existing = DB::table('doc_files')
                ->whereNotNull('team_id')
                ->select(['id as document_id', 'team_id', 'created_at', 'updated_at'])
                ->get();

            foreach ($existing as $row) {
                DB::table('doc_file_teams')->updateOrInsert(
                    ['document_id' => $row->document_id, 'team_id' => $row->team_id],
                    ['created_at' => $row->created_at ?? now(), 'updated_at' => $row->updated_at ?? now()],
                );
            }

            Schema::table('doc_files', function (Blueprint $table) {
                $table->dropForeign(['team_id']);
                $table->dropIndex(['team_id', 'created_at']);
                $table->dropColumn('team_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('doc_files', 'team_id')) {
            Schema::table('doc_files', function (Blueprint $table) {
                $table->foreignId('team_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('mst_teams')
                      ->onDelete('cascade');
                $table->index(['team_id', 'created_at']);
            });
        }

        Schema::dropIfExists('doc_file_teams');
    }
};
