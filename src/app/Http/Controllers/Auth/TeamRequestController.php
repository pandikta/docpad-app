<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TeamRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class TeamRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/RequestTeam', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, TeamRequestService $teamRequests): RedirectResponse
    {
        $validated = $request->validate([
            'team_code' => ['required', 'string', 'max:50', 'alpha_dash'],
            'team_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'company_email' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $teamRequests->submit($validated);

        return redirect()->route('team-request.create')
            ->with('status', 'submitted');
    }
}
