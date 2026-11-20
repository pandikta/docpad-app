<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use App\Services\ExtensionService;
use App\Services\LoginActivityService;
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
        LoginActivityService $loginActivity,
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
            'maxFileUploads' => max(1, (int) (ini_get('max_file_uploads') ?: 20)),
            'maxPostSizeBytes' => $this->parseIniSizeBytes((string) (ini_get('post_max_size') ?: '50M')),
            'storageQuota' => $documents->getStorageQuotaSummary($user),
            'teams' => is_superadmin($user) ? $teams->listActiveTeams() : [],
            'myTeam' => $myTeam,
            'pendingJoinRequest' => $membership->getPendingJoinRequestForUser($user),
            'loginSessions' => $loginActivity->listActiveSessions($request),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    private function parseIniSizeBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '' || $val === '-1') {
            return 0;
        }

        $unit = strtolower(substr($val, -1));
        $num = (int) $val;

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}
