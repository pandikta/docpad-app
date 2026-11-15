<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teams,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/MasterTeams', [
            'teams' => $this->teams->listAllForManagement(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:mst_teams,team_code'],
            'team_name' => ['required', 'string', 'max:100'],
            'max_member' => ['required', 'integer', 'min:0', 'max:10000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $team = $this->teams->create($validated);

        return back()->with('success', "Team {$team->team_name} created.");
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $validated = $request->validate([
            'team_code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('mst_teams', 'team_code')->ignore($team->id),
            ],
            'team_name' => ['required', 'string', 'max:100'],
            'max_member' => ['required', 'integer', 'min:0', 'max:10000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $this->teams->update($team, $validated);

        return back()->with('success', "Team {$team->team_name} updated.");
    }

    public function destroy(Team $team): RedirectResponse
    {
        $name = $team->team_name;
        $this->teams->delete($team);

        return back()->with('success', "Team {$name} deleted.");
    }
}
