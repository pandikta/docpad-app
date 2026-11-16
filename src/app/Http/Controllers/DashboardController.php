<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use App\Services\ExtensionService;
use App\Services\TeamMembershipService;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard (document list milik team user).
     */
    public function index(
        Request $request,
        DocumentService $documents,
        TeamService $teams,
        ExtensionService $extensions,
        TeamMembershipService $membership,
    ): Response {
        $user = $request->user();
        $search = $request->string('search')->toString();

        $myTeam = null;
        if ($user->team_id !== null) {
            $team = $user->team()
                ->with(['users' => fn ($q) => $q
                    ->select('id', 'team_id', 'name', 'email', 'is_team_leader')
                    ->orderByDesc('is_team_leader')
                    ->orderBy('name')])
                ->first();

            if ($team) {
                $myTeam = [
                    'id' => $team->id,
                    'name' => $team->team_name,
                    'code' => $team->team_code,
                    'members' => $team->users->map(fn ($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                        'email' => $m->email,
                        'is_team_leader' => (bool) $m->is_team_leader,
                        'is_me' => $m->id === $user->id,
                    ])->values(),
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'documents' => $documents->listForUser($user, $search),
            'folders' => $documents->listFolders(),
            'allowedExtensions' => allowed_document_extensions(),
            'creatableExtensions' => $extensions->listCreatableFromFormCodes(),
            'extensionMaxSizes' => $extensions->listActiveWithLimits(),
            'storageQuota' => $documents->getStorageQuotaSummary($user),
            'teams' => is_superadmin($user) ? $teams->listActiveTeams() : [],
            'myTeam' => $myTeam,
            'pendingJoinRequest' => $membership->getPendingJoinRequestForUser($user),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
