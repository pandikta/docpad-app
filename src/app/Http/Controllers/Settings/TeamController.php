<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamLeaderRequest;
use App\Models\TeamRequest;
use App\Services\TeamLeaderRequestService;
use App\Services\TeamRequestService;
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
        private readonly TeamRequestService $teamRequests,
        private readonly TeamLeaderRequestService $leaderRequests,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Settings/MasterTeams', [
            'teams' => $this->teams->listAllForManagement(),
            'teamRequests' => $this->teamRequests->listForManagement(),
            'leaderRequests' => $this->leaderRequests->listForManagement(),
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
            'allow_multiple_leaders' => ['boolean'],
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
            'allow_multiple_leaders' => ['boolean'],
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

    public function approveRequest(Request $request, TeamRequest $teamRequest): RedirectResponse
    {
        $team = $this->teamRequests->approve($teamRequest, $request->user());

        return back()->with('success', "Team {$team->team_name} ({$team->team_code}) approved and {$teamRequest->requester_name} assigned as Team Leader.");
    }

    public function rejectRequest(Request $request, TeamRequest $teamRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->teamRequests->reject($teamRequest, $request->user(), $validated['rejection_reason'] ?? null);

        return back()->with('success', "Team request for {$teamRequest->team_name} rejected.");
    }

    public function approveLeaderRequest(Request $request, TeamLeaderRequest $leaderRequest): RedirectResponse
    {
        $this->leaderRequests->approve($leaderRequest, $request->user());

        return back()->with('success', "{$leaderRequest->user?->name} is now Team Leader for {$leaderRequest->team?->team_name}.");
    }

    public function rejectLeaderRequest(Request $request, TeamLeaderRequest $leaderRequest): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->leaderRequests->reject($leaderRequest, $request->user(), $validated['rejection_reason'] ?? null);

        return back()->with('success', 'Team Leader request rejected.');
    }
}
