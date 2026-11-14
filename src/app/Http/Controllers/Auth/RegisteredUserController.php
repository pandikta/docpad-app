<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, TeamService $teams, UserService $users): RedirectResponse
    {
        $request->validate([
            'team_code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Cek dulu apakah team dengan nama tersebut sudah ada (dan aktif).
        $team = $teams->findActiveByCode($request->team_code);

        if (! $team) {
            throw ValidationException::withMessages([
                'team_code' => 'Team not found. Please check the team name or contact admin for team name.',
            ]);
        }

        if ($teams->hasReachedMaxMembers($team)) {
            throw ValidationException::withMessages([
                'team_code' => "This team has reached its maximum member limit ({$team->max_member}). Please contact admin.",
            ]);
        }

        $user = $users->registerForTeam($team, $request->only('name', 'email', 'password'));

        event(new Registered($user));
    
        return redirect(route('login', absolute: false))
            ->with('status', 'Account created successfully! Please sign in.');
    }
}
