<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('doc_files')
                ->cascadeOnDelete();
            $table->foreignId('created_by')
                ->constrained('mst_users')
                ->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_share_links');
    }
};
