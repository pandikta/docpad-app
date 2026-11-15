<?php

namespace Database\Seeders;

use App\Models\AppMenu;
use Illuminate\Database\Seeder;

class MenuAuthSeeder extends Seeder
{
    /**
     * Daftar menu aplikasi yang hak aksesnya bisa diatur lewat Menu Authorization
     * sekaligus menjadi sumber menu dinamis di Sidebar.
     */
    public function run(): void
    {
        $menus = [
            [
                'code' => 'menu.dashboard',
                'name' => 'Documents',
                'group' => 'Main Navigation',
                'section' => 'main',
                'route_name' => 'dashboard',
                'icon' => 'pi pi-home',
                'description' => 'Main document workspace and listing page',
                'default_allowed' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'menu.profile',
                'name' => 'Profile',
                'group' => 'Main Navigation',
                'section' => 'main',
                'route_name' => 'profile.edit',
                'icon' => 'pi pi-user',
                'description' => 'User profile, password, and account settings',
                'default_allowed' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'menu.team-control',
                'name' => 'Team Control',
                'group' => 'Main Navigation',
                'section' => 'main',
                'route_name' => 'team-control.index',
                'icon' => 'pi pi-shield',
                'description' => 'Manage team active share links and member document permissions',
                'default_allowed' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'menu.settings.extensions',
                'name' => 'Master Extensions',
                'group' => 'Master Setting',
                'section' => 'settings',
                'route_name' => 'settings.extensions.index',
                'icon' => 'pi pi-file-check',
                'description' => 'Manage allowed document extensions',
                'default_allowed' => false,
                'sort_order' => 10,
            ],
            [
                'code' => 'menu.settings.teams',
                'name' => 'Master Teams',
                'group' => 'Master Setting',
                'section' => 'settings',
                'route_name' => 'settings.teams.index',
                'icon' => 'pi pi-users',
                'description' => 'Manage workspace teams & codes',
                'default_allowed' => false,
                'sort_order' => 11,
            ],
            [
                'code' => 'menu.settings.users',
                'name' => 'Master Users',
                'group' => 'Master Setting',
                'section' => 'settings',
                'route_name' => 'settings.users.index',
                'icon' => 'pi pi-user-edit',
                'description' => 'Manage registered users',
                'default_allowed' => false,
                'sort_order' => 12,
            ],
            [
                'code' => 'menu.settings.function-auth',
                'name' => 'Function Authorization',
                'group' => 'Access & Authorization',
                'section' => 'settings',
                'route_name' => 'settings.function-auth.index',
                'icon' => 'pi pi-shield',
                'description' => 'Manage action permissions per team or user',
                'default_allowed' => false,
                'sort_order' => 20,
            ],
            [
                'code' => 'menu.settings.menu-auth',
                'name' => 'Menu Authorization',
                'group' => 'Access & Authorization',
                'section' => 'settings',
                'route_name' => 'settings.menu-auth.index',
                'icon' => 'pi pi-sitemap',
                'description' => 'Manage accessible menus per team or user',
                'default_allowed' => false,
                'sort_order' => 21,
            ],
        ];

        foreach ($menus as $menu) {
            AppMenu::updateOrCreate(
                ['code' => $menu['code']],
                $menu,
            );
        }
    }
}
