<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trx_team_requests', function (Blueprint $table) {
            $table->id();
            $table->string('team_code', 50);
            $table->string('team_name', 100);
            $table->unsignedInteger('max_member')->default(10);
            $table->string('description', 500)->nullable();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('password');
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('mst_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_team_id')->nullable()->constrained('mst_teams')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trx_team_requests');
    }
};
