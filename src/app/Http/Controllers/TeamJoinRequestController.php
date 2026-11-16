<?php

namespace App\Http\Controllers;

use App\Services\TeamMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamJoinRequestController extends Controller
{
    public function store(Request $request, TeamMembershipService $membership): RedirectResponse
    {
        $validated = $request->validate([
            'team_code' => ['required', 'string', 'max:50'],
        ]);

        $joinRequest = $membership->submitJoinRequest($request->user(), $validated['team_code']);

        return back()->with(
            'success',
            "Join request for {$joinRequest->team?->team_name} submitted and waiting for approval.",
        );
    }
}
