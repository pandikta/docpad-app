<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_team_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('mst_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('mst_users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('mst_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_team_join_requests');
    }
};
