<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordHistoryService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly PasswordHistoryService $passwordHistory,
    ) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->userService->updatePassword($request->user(), $validated['password']);

        if ($request->user()->is_reset) {
            $request->user()->forceFill(['is_reset' => false])->save();
        }

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Selesaikan paksa ganti password setelah akun di-reset oleh Superadmin.
     */
    public function forceUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless((bool) $user->is_reset, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $this->passwordHistory->ensureNotRecentlyUsed($user, $validated['password']);

        $user->forceFill([
            'password' => $validated['password'],
            'is_reset' => false,
        ])->save();

        $this->passwordHistory->record($user, $user->password);

        return back()->with('success', 'Password updated successfully.');
    }
}
