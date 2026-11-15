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
        Schema::create('mst_menus', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group')->default('Main');
            $table->string('route_name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('default_allowed')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mst_menu_auths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')
                  ->constrained('mst_menus')
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

            $table->unique(['menu_id', 'team_id']);
            $table->unique(['menu_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_menu_auths');
        Schema::dropIfExists('mst_menus');
    }
};
