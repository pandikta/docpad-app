<?php

namespace App\Http\Controllers;

use App\Services\TeamLeaderRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamLeaderRequestController extends Controller
{
    public function store(Request $request, TeamLeaderRequestService $leaderRequests): RedirectResponse
    {
        $result = $leaderRequests->requestLeadership($request->user());

        if ($result['auto_approved']) {
            return back()->with('success', 'You are now the Team Leader for your team.');
        }

        return back()->with('success', 'Team Leader request submitted and waiting for admin approval.');
    }
}
