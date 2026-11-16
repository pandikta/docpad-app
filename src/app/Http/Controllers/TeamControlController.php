<?php

namespace App\Http\Controllers;

use App\Models\AppFunction;
use App\Models\DocumentShareLink;
use App\Models\FunctionAuth;
use App\Models\TeamJoinRequest;
use App\Models\User;
use App\Services\FunctionAuthService;
use App\Services\TeamMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamControlController extends Controller
{
    public function __construct(
        private readonly FunctionAuthService $functionAuth,
        private readonly TeamMembershipService $membership,
    ) {}

    /**
     * Halaman Team Control untuk Team Leader (PM) dan Superadmin:
     * - Kontrol keamanan Shareable Links milik team
     * - Pengaturan hak akses dasar anggota team (documents.*)
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $isSuperAdmin = $user->isSuperAdmin();

        // 1. Active Shareable Links (Point 3)
        $shareLinksQuery = DocumentShareLink::with(['document:id,name,user_id', 'document.teams:id,team_name', 'creator:id,name'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('created_at');

        if (! $isSuperAdmin) {
            $shareLinksQuery->whereHas('document.teams', fn ($q) => $q->where('mst_teams.id', $user->team_id));
        }

        $shareLinks = $shareLinksQuery->get()->map(fn (DocumentShareLink $link) => [
            'id' => $link->id,
            'token' => $link->token,
            'url' => route('share.show', $link->token),
            'expires_at' => $link->expires_at?->toIso8601String(),
            'created_at' => $link->created_at?->toIso8601String(),
            'creator_name' => $link->creator?->name ?? '-',
            'document_id' => $link->document_id,
            'document_name' => $link->document?->name ?? 'Deleted Document',
            'teams' => $link->document?->teams->pluck('team_name')->values()->all() ?? [],
        ]);

        // 2. Member Permissions (Point 4)
        $membersQuery = User::where('is_superadmin', false)
            ->whereNotNull('team_id')
            ->with('team:id,team_name')
            ->orderBy('name');

        if (! $isSuperAdmin) {
            $membersQuery
                ->where('team_id', $user->team_id)
                ->where('id', '!=', $user->id);
        }

        $members = $membersQuery->get(['id', 'name', 'email', 'team_id', 'is_team_leader'])
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'team_id' => $member->team_id,
                'team_name' => $member->team?->team_name,
                'is_team_leader' => (bool) $member->is_team_leader,
            ]);

        $functions = AppFunction::where('code', 'like', 'documents.%')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'description', 'default_allowed']);

        $functionIds = $functions->pluck('id');
        $memberIds = $members->pluck('id');
        $teamIds = $members->pluck('team_id')->filter()->unique();

        $teamRules = FunctionAuth::whereIn('function_id', $functionIds)
            ->whereIn('team_id', $teamIds)
            ->get(['function_id', 'team_id', 'is_allowed']);

        $userRules = FunctionAuth::whereIn('function_id', $functionIds)
            ->whereIn('user_id', $memberIds)
            ->get(['function_id', 'user_id', 'is_allowed']);

        return Inertia::render('TeamControl/Index', [
            'teamName' => $isSuperAdmin ? 'All Teams' : ($user->team?->team_name ?? 'My Team'),
            'shareLinks' => $shareLinks,
            'members' => $members,
            'functions' => $functions,
            'teamRules' => $teamRules,
            'userRules' => $userRules,
            'joinRequests' => $this->membership->listJoinRequestsForActor($user),
        ]);
    }

    /**
     * Revoke active shareable link milik team dari halaman Team Control.
     */
    public function revokeShareLink(Request $request, DocumentShareLink $shareLink): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $shareLink->loadMissing('document.teams');

        if (! $user->isSuperAdmin()) {
            $belongsToTeam = $shareLink->document
                && $shareLink->document->teams()->where('mst_teams.id', $user->team_id)->exists();

            abort_unless($belongsToTeam, 403);
        }

        $shareLink->update(['is_active' => false]);

        if ($shareLink->document) {
            record_document_log($shareLink->document, 'revoked shareable read-only link', $user);
        }

        return back()->with('success', 'Shareable link revoked.');
    }

    /**
     * Ubah izin fungsi dokumen (documents.*) untuk anggota team.
     */
    public function updatePermission(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $validated = $request->validate([
            'function_id' => ['required', 'integer', 'exists:mst_functions,id'],
            'user_id' => ['required', 'integer', 'exists:mst_users,id'],
            'is_allowed' => ['required', 'boolean'],
        ]);

        $targetUser = User::findOrFail((int) $validated['user_id']);
        $appFunction = AppFunction::findOrFail((int) $validated['function_id']);

        $this->authorizeMemberPermissionEdit($user, $targetUser, $appFunction);

        $this->functionAuth->updatePermission(
            $appFunction->id,
            (bool) $validated['is_allowed'],
            null,
            $targetUser->id,
        );

        return back()->with('success', "Updated {$appFunction->name} for {$targetUser->name}.");
    }

    /**
     * Reset override izin anggota agar kembali mengikuti aturan default team.
     */
    public function resetPermission(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $validated = $request->validate([
            'function_id' => ['required', 'integer', 'exists:mst_functions,id'],
            'user_id' => ['required', 'integer', 'exists:mst_users,id'],
        ]);

        $targetUser = User::findOrFail((int) $validated['user_id']);
        $appFunction = AppFunction::findOrFail((int) $validated['function_id']);

        $this->authorizeMemberPermissionEdit($user, $targetUser, $appFunction);

        $this->functionAuth->resetUserOverride($appFunction->id, $targetUser->id);

        return back()->with('success', "Reset {$appFunction->name} for {$targetUser->name} to team default.");
    }

    public function takeOutMember(Request $request, User $member): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $this->membership->takeOutMember($user, $member);

        return back()->with('success', "{$member->name} has been removed from the team.");
    }

    public function approveJoinRequest(Request $request, TeamJoinRequest $joinRequest): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $this->membership->approveJoinRequest($joinRequest, $user);

        return back()->with('success', "{$joinRequest->user?->name} has been added to {$joinRequest->team?->team_name}.");
    }

    public function rejectJoinRequest(Request $request, TeamJoinRequest $joinRequest): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeTeamControl($user);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->membership->rejectJoinRequest($joinRequest, $user, $validated['rejection_reason'] ?? null);

        return back()->with('success', 'Join request rejected.');
    }

    private function authorizeTeamControl(User $user): void
    {
        abort_unless($user->isSuperAdmin() || $user->team_id !== null, 403);
    }

    private function authorizeMemberPermissionEdit(User $actor, User $targetUser, AppFunction $appFunction): void
    {
        abort_if($targetUser->isSuperAdmin(), 403);
        abort_unless(str_starts_with($appFunction->code, 'documents.'), 403);

        if (! $actor->isSuperAdmin()) {
            abort_unless(
                $targetUser->team_id === $actor->team_id && $targetUser->id !== $actor->id,
                403,
            );
        }
    }
}
