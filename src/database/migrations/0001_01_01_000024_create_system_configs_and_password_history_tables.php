<?php

use App\Models\AppMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('mst_system_configs')->insert([
            'key' => 'password_history_limit',
            'value' => '2',
            'description' => 'Number of previous passwords that cannot be reused (0 = no restriction)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('mst_user_password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('mst_users')
                ->cascadeOnDelete();
            $table->string('password_hash');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'id']);
        });

        AppMenu::updateOrCreate(
            ['code' => 'menu.settings.system-config'],
            [
                'name' => 'System Config',
                'group' => 'Master Setting',
                'section' => 'settings',
                'route_name' => 'settings.system-config.index',
                'icon' => 'pi pi-sliders-h',
                'description' => 'Configure application security and password policies',
                'default_allowed' => false,
                'sort_order' => 6,
            ],
        );
    }

    public function down(): void
    {
        AppMenu::where('code', 'menu.settings.system-config')->delete();
        Schema::dropIfExists('mst_user_password_histories');
        Schema::dropIfExists('mst_system_configs');
    }
};
