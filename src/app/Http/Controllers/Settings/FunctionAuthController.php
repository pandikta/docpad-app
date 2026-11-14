<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\FunctionAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FunctionAuthController extends Controller
{
    public function __construct(
        private readonly FunctionAuthService $functionAuth,
    ) {}

    /**
     * Tampilkan halaman pengaturan Function Authorization (khusus Superadmin).
     */
    public function index(): Response
    {
        return Inertia::render('Settings/FunctionAuth', $this->functionAuth->getManagementData());
    }

    /**
     * Simpan perubahan hak akses fungsi untuk Team atau User.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'function_id' => ['required', 'integer', 'exists:mst_functions,id'],
            'team_id' => ['nullable', 'integer', 'exists:mst_teams,id', 'required_without:user_id'],
            'user_id' => ['nullable', 'integer', 'exists:mst_users,id', 'required_without:team_id'],
            'is_allowed' => ['required', 'boolean'],
        ]);

        $this->functionAuth->updatePermission(
            (int) $validated['function_id'],
            (bool) $validated['is_allowed'],
            isset($validated['team_id']) ? (int) $validated['team_id'] : null,
            isset($validated['user_id']) ? (int) $validated['user_id'] : null,
        );

        return back()->with('success', 'Function authorization updated.');
    }

    /**
     * Reset override user agar kembali mengikuti aturan timnya.
     */
    public function resetUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'function_id' => ['required', 'integer', 'exists:mst_functions,id'],
            'user_id' => ['required', 'integer', 'exists:mst_users,id'],
        ]);

        $this->functionAuth->resetUserOverride(
            (int) $validated['function_id'],
            (int) $validated['user_id'],
        );

        return back()->with('success', 'User permission reset to team default.');
    }
}
