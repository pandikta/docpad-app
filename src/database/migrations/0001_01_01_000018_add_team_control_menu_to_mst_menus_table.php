<?php

use App\Models\AppMenu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppMenu::updateOrCreate(
            ['code' => 'menu.team-control'],
            [
                'name' => 'Team Control',
                'group' => 'Main Navigation',
                'section' => 'main',
                'route_name' => 'team-control.index',
                'icon' => 'pi pi-shield',
                'description' => 'Manage team active share links and member document permissions',
                'default_allowed' => false,
                'sort_order' => 3,
            ],
        );
    }

    public function down(): void
    {
        AppMenu::where('code', 'menu.team-control')->delete();
    }
};
