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
        Schema::create('mst_functions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group')->default('General');
            $table->string('description')->nullable();
            $table->boolean('default_allowed')->default(true);
            $table->timestamps();
        });

        Schema::create('mst_function_auths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('function_id')
                  ->constrained('mst_functions')
                  ->onDelete('cascade');
            $table->foreignId('team_id')
                  ->nullable()
                  ->constrained('mst_teams')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('mst_users')
                  ->onDelete('cascade');
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();

            $table->unique(['function_id', 'team_id']);
            $table->unique(['function_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_function_auths');
        Schema::dropIfExists('mst_functions');
    }
};
