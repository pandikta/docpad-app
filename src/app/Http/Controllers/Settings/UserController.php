<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly TeamService $teams,
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
}
