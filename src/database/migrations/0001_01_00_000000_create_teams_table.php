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
        Schema::dropIfExists('mst_teams');
        Schema::enableForeignKeyConstraints();

        Schema::create('mst_teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_code')->unique();
            $table->string('team_name');
            $table->integer('max_member')->default(1);
            $table->text('description')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('mst_teams');
        Schema::dropIfExists('teams');
        Schema::enableForeignKeyConstraints();
    }
};
