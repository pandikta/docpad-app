<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppMenu;
use App\Services\MenuAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MenuAuthController extends Controller
{
    public function __construct(
        private readonly MenuAuthService $menuAuth,
    ) {}

    /**
     * Tampilkan halaman pengaturan Menu Authorization.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/MenuAuth', $this->menuAuth->getManagementData());
    }

    /**
     * Simpan perubahan hak akses menu untuk Team atau User.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:mst_menus,id'],
            'team_id' => ['nullable', 'integer', 'exists:mst_teams,id', 'required_without:user_id'],
            'user_id' => ['nullable', 'integer', 'exists:mst_users,id', 'required_without:team_id'],
            'is_allowed' => ['required', 'boolean'],
        ]);

        $this->menuAuth->updatePermission(
            (int) $validated['menu_id'],
            (bool) $validated['is_allowed'],
            isset($validated['team_id']) ? (int) $validated['team_id'] : null,
            isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        );

        return back()->with('success', 'Menu authorization updated.');
    }

    /**
     * Reset override user agar kembali mengikuti aturan timnya.
     */
    public function resetUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:mst_menus,id'],
            'user_id' => ['required', 'integer', 'exists:mst_users,id'],
        ]);

        $this->menuAuth->resetUserOverride(
            (int) $validated['menu_id'],
            (int) $validated['user_id'],
        );

        return back()->with('success', 'User menu permission reset to team default.');
    }

    /**
     * Tambah menu baru langsung dari UI agar otomatis muncul di Sidebar/Settings.
     */
    public function storeMenu(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:mst_menus,code'],
            'name' => ['required', 'string', 'max:100'],
            'group' => ['required', 'string', 'max:100'],
            'section' => ['required', 'in:main,settings'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'default_allowed' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $menu = $this->menuAuth->createMenu($validated);

        return back()->with('success', "Menu \"{$menu->name}\" added.");
    }

    /**
     * Perbarui data menu.
     */
    public function updateMenu(Request $request, AppMenu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('mst_menus', 'code')->ignore($menu->id)],
            'name' => ['required', 'string', 'max:100'],
            'group' => ['required', 'string', 'max:100'],
            'section' => ['required', 'in:main,settings'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'default_allowed' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $this->menuAuth->updateMenu($menu, $validated);

        return back()->with('success', "Menu \"{$menu->name}\" updated.");
    }

    /**
     * Hapus menu dari database.
     */
    public function destroyMenu(AppMenu $menu): RedirectResponse
    {
        $name = $menu->name;
        $this->menuAuth->deleteMenu($menu);

        return back()->with('success', "Menu \"{$name}\" deleted.");
    }
}
