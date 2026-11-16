<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminPasswordChangedNotification;
use App\Services\PasswordHistoryService;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly TeamService $teams,
        private readonly PasswordHistoryService $passwordHistory,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/MasterUsers', [
            'users' => $this->users->listAllForManagement(),
            'teams' => $this->teams->listActiveTeams(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:mst_users,email'],
            'password' => ['required', Password::defaults()],
            'team_id' => ['nullable', 'integer', 'exists:mst_teams,id', 'required_if:is_superadmin,false'],
            'is_superadmin' => ['boolean'],
            'is_team_leader' => ['boolean'],
            'storage_limit_mb' => ['nullable', 'integer', 'min:1', 'max:1048576'],
        ]);

        $user = $this->users->createUser($validated);

        return back()->with('success', "User {$user->name} created.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('mst_users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', Password::defaults()],
            'team_id' => ['nullable', 'integer', 'exists:mst_teams,id', 'required_if:is_superadmin,false'],
            'is_superadmin' => ['boolean'],
            'is_team_leader' => ['boolean'],
            'storage_limit_mb' => ['nullable', 'integer', 'min:1', 'max:1048576'],
        ]);

        $this->users->updateUser($user, $validated);

        return back()->with('success', "User {$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return back()->withErrors(['user' => 'You cannot delete your own active account here.']);
        }

        $name = $user->name;
        $this->users->deleteAccount($user);

        return back()->with('success', "User {$name} deleted.");
    }

    /**
     * Reset password user secara langsung oleh Superadmin (plus self notif in-app & email).
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $this->passwordHistory->ensureNotRecentlyUsed($user, $validated['password']);

        $user->forceFill([
            'password' => $validated['password'],
            'is_reset' => true,
            'last_reset' => now(),
        ])->save();

        $this->passwordHistory->record($user, $user->password);

        $admin = $request->user();
        $user->notify(new AdminPasswordChangedNotification($admin->id, $admin->name, ['database']));

        $mailWarning = null;
        try {
            $user->notify(new AdminPasswordChangedNotification($admin->id, $admin->name, ['mail']));
        } catch (\Throwable) {
            $mailWarning = 'Password updated, but email notification could not be sent.';
        }

        $redirect = back()->with('success', "Password for {$user->name} has been updated.");

        return $mailWarning ? $redirect->with('warning', $mailWarning) : $redirect;
    }

    /**
     * Generate random password, simpan ke user, dan kirim via email + self notif in-app.
     */
    public function sendResetLink(Request $request, User $user): RedirectResponse
    {
        $randomPassword = $this->generateStrongPassword();
        $admin = $request->user();

        try {
            $user->notifyNow(new AdminPasswordChangedNotification($admin->id, $admin->name, ['mail'], $randomPassword));
        } catch (\Throwable) {
            return back()->withErrors(['email' => 'Failed to send password email. Check mail configuration.']);
        }

        $user->forceFill([
            'password' => $randomPassword,
            'is_reset' => true,
            'last_reset' => now(),
        ])->save();

        $this->passwordHistory->record($user, $user->password);

        $user->notify(new AdminPasswordChangedNotification($admin->id, $admin->name, ['database']));

        return back()->with('success', "New random password generated and sent to {$user->email}.");
    }

    private function generateStrongPassword(): string
    {
        $upper = Str::upper(Str::random(3));
        $lower = Str::lower(Str::random(4));
        $digits = (string) random_int(100, 999);
        $symbols = '!@#$%&*';
        $symbol = $symbols[random_int(0, strlen($symbols) - 1)].$symbols[random_int(0, strlen($symbols) - 1)];

        return str_shuffle($upper.$lower.$digits.$symbol);
    }
}
