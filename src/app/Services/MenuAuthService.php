<?php

namespace App\Services;

use App\Models\AppMenu;
use App\Models\MenuAuth;
use App\Models\Team;
use App\Models\User;

class MenuAuthService
{
    /**
     * Cek apakah user berhak mengakses menu tertentu.
     * Prioritas:
     * 1. Superadmin -> selalu true
     * 2. Override khusus user (user_id)
     * 3. Aturan team (team_id)
     * 4. Nilai default_allowed pada mst_menus
     */
    public function can(User $user, string $menuCode): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getPermissionsMap($user);

        return $permissions[$menuCode] ?? false;
    }

    /**
     * Peta seluruh menu code => boolean untuk user yang sedang login.
     *
     * @return array<string, bool>
     */
    public function getPermissionsMap(User $user): array
    {
        $menus = AppMenu::all(['id', 'code', 'default_allowed']);

        if ($user->isSuperAdmin()) {
            return $menus->mapWithKeys(fn (AppMenu $menu) => [$menu->code => true])->all();
        }

        $teamRules = $user->team_id
            ? MenuAuth::where('team_id', $user->team_id)->pluck('is_allowed', 'menu_id')
            : collect();

        $userRules = MenuAuth::where('user_id', $user->id)->pluck('is_allowed', 'menu_id');

        return $menus->mapWithKeys(function (AppMenu $menu) use ($userRules, $teamRules) {
            if ($userRules->has($menu->id)) {
                return [$menu->code => (bool) $userRules->get($menu->id)];
            }

            if ($teamRules->has($menu->id)) {
                return [$menu->code => (bool) $teamRules->get($menu->id)];
            }

            return [$menu->code => (bool) $menu->default_allowed];
        })->all();
    }

    /**
     * Daftar menu yang boleh diakses user untuk dirender dinamis di Sidebar & Settings panel.
     *
     * @return array{main: array<int, array<string, mixed>>, settings: array<int, array<string, mixed>>}
     */
    public function getVisibleMenusForUser(User $user): array
    {
        $permissions = $this->getPermissionsMap($user);
        $menus = AppMenu::orderBy('sort_order')->orderBy('id')->get();

        $visible = $menus->filter(
            fn (AppMenu $menu) => $user->isSuperAdmin() || ($permissions[$menu->code] ?? false),
        );

        $mapItem = fn (AppMenu $menu) => [
            'id' => $menu->id,
            'code' => $menu->code,
            'name' => $menu->name,
            'group' => $menu->group,
            'section' => $menu->section,
            'route_name' => $menu->route_name,
            'icon' => $menu->icon ?: 'pi pi-circle',
            'description' => $menu->description,
        ];

        return [
            'main' => $visible->where('section', 'main')->values()->map($mapItem)->all(),
            'settings' => $visible->where('section', 'settings')->values()->map($mapItem)->all(),
        ];
    }

    /**
     * Data lengkap untuk halaman manajemen Menu Authorization.
     *
     * @return array<string, mixed>
     */
    public function getManagementData(): array
    {
        $menus = AppMenu::orderBy('sort_order')->orderBy('id')->get([
            'id', 'code', 'name', 'group', 'section', 'route_name', 'icon', 'description', 'default_allowed', 'sort_order',
        ]);

        $teams = Team::where('is_active', 1)
            ->orderBy('team_name')
            ->get(['id', 'team_name', 'team_code'])
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->team_name,
                'code' => $team->team_code,
            ]);

        $users = User::where('is_superadmin', false)
            ->with('team:id,team_name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'team_id'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team_id' => $user->team_id,
                'team_name' => $user->team?->team_name,
            ]);

        $authRules = MenuAuth::all(['id', 'menu_id', 'team_id', 'user_id', 'is_allowed']);

        return [
            'menus' => $menus,
            'teams' => $teams,
            'users' => $users,
            'authRules' => $authRules,
        ];
    }

    /**
     * Simpan atau perbarui aturan izin menu untuk Team atau User tertentu.
     */
    public function updatePermission(int $menuId, bool $isAllowed, ?int $teamId = null, ?int $userId = null): MenuAuth
    {
        $match = $teamId !== null
            ? ['menu_id' => $menuId, 'team_id' => $teamId]
            : ['menu_id' => $menuId, 'user_id' => $userId];

        return MenuAuth::updateOrCreate($match, [
            'team_id' => $teamId,
            'user_id' => $userId,
            'is_allowed' => $isAllowed,
        ]);
    }

    /**
     * Hapus override user tertentu agar kembali mengikuti aturan timnya.
     */
    public function resetUserOverride(int $menuId, int $userId): void
    {
        MenuAuth::where('menu_id', $menuId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Tambah menu baru ke database agar otomatis muncul di Sidebar/Settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function createMenu(array $data): AppMenu
    {
        return AppMenu::create([
            'code' => trim($data['code']),
            'name' => trim($data['name']),
            'group' => trim($data['group']),
            'section' => $data['section'],
            'route_name' => isset($data['route_name']) ? trim($data['route_name']) : null,
            'icon' => isset($data['icon']) && trim($data['icon']) !== '' ? trim($data['icon']) : 'pi pi-circle',
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'default_allowed' => (bool) ($data['default_allowed'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 50),
        ]);
    }

    /**
     * Perbarui data menu.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMenu(AppMenu $menu, array $data): AppMenu
    {
        $menu->update([
            'code' => trim($data['code']),
            'name' => trim($data['name']),
            'group' => trim($data['group']),
            'section' => $data['section'],
            'route_name' => isset($data['route_name']) ? trim($data['route_name']) : null,
            'icon' => isset($data['icon']) && trim($data['icon']) !== '' ? trim($data['icon']) : 'pi pi-circle',
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'default_allowed' => (bool) ($data['default_allowed'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 50),
        ]);

        return $menu;
    }

    /**
     * Hapus menu dari database.
     */
    public function deleteMenu(AppMenu $menu): void
    {
        $menu->delete();
    }
}
