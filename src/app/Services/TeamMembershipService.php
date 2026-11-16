<?php

namespace App\Services;

use App\Models\FunctionAuth;
use App\Models\SystemConfig;
use App\Models\Team;
use App\Models\TeamJoinRequest;
use App\Models\User;
use App\Notifications\TeamMembershipNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TeamMembershipService
{
    public function __construct(
        private readonly TeamService $teams,
    ) {}

    public function takeOutMember(User $actor, User $targetUser): void
    {
        if ($targetUser->isSuperAdmin() || $targetUser->id === $actor->id || ! $targetUser->team_id) {
            abort(403, 'Cannot remove this user from team.');
        }

        if (! $actor->isSuperAdmin() && (! $actor->isTeamLeader() || $targetUser->team_id !== $actor->team_id)) {
            abort(403, 'You are not allowed to remove members from this team.');
        }

        $team = $targetUser->team;

        DB::transaction(function () use ($targetUser) {
            $targetUser->forceFill([
                'team_id' => null,
                'is_team_leader' => false,
            ])->save();

            FunctionAuth::where('user_id', $targetUser->id)->delete();
        });

        if ($team) {
            $this->notifyUsers(collect([$targetUser]), 'removed_from_team', $actor, $team);
        }
    }

    public function submitJoinRequest(User $user, string $teamCode): TeamJoinRequest
    {
        if ($user->isSuperAdmin() || $user->team_id !== null) {
            throw ValidationException::withMessages([
                'team_code' => 'You already belong to a team.',
            ]);
        }

        $team = $this->teams->findActiveByCode($teamCode);
        if (! $team) {
            throw ValidationException::withMessages([
                'team_code' => 'Team not found or inactive. Please check the team code.',
            ]);
        }

        if ($this->teams->hasReachedMaxMembers($team)) {
            throw ValidationException::withMessages([
                'team_code' => "This team has reached its maximum member limit ({$team->max_member}).",
            ]);
        }

        $alreadyPending = TeamJoinRequest::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            throw ValidationException::withMessages([
                'team_code' => 'You already have a pending join request for this team.',
            ]);
        }

        $joinRequest = TeamJoinRequest::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Notify Team Leader(s) if any, plus Superadmins as fallback/oversight
        $leaders = User::where('team_id', $team->id)
            ->where('is_team_leader', true)
            ->get();

        $superadmins = User::where('is_superadmin', true)->get();
        $recipients = $leaders->merge($superadmins)->unique('id');

        if ($recipients->isNotEmpty()) {
            $this->notifyUsers($recipients, 'join_team_requested', $user, $team, $joinRequest->id);
        }

        return $joinRequest;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPendingJoinRequestForUser(?User $user): ?array
    {
        if (! $user || $user->isSuperAdmin() || $user->team_id !== null || ! Schema::hasTable('trx_team_join_requests')) {
            return null;
        }

        $req = TeamJoinRequest::with('team:id,team_name,team_code')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if (! $req || ! $req->team) {
            return null;
        }

        $hasLeader = User::where('team_id', $req->team_id)
            ->where('is_team_leader', true)
            ->exists();

        return [
            'id' => $req->id,
            'team_name' => $req->team->team_name,
            'team_code' => $req->team->team_code,
            'has_team_leader' => $hasLeader,
            'created_at' => $req->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listJoinRequestsForActor(User $actor): Collection
    {
        if (! Schema::hasTable('trx_team_join_requests')) {
            return collect();
        }

        $query = TeamJoinRequest::with([
            'team:id,team_name,team_code',
            'user:id,name,email',
            'reviewer:id,name',
        ]);

        if (! $actor->isSuperAdmin()) {
            $query->where('team_id', $actor->team_id);
        }

        return $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TeamJoinRequest $req) => [
                'id' => $req->id,
                'team_id' => $req->team_id,
                'team_name' => $req->team?->team_name ?? '',
                'team_code' => $req->team?->team_code ?? '',
                'user_id' => $req->user_id,
                'user_name' => $req->user?->name ?? '',
                'user_email' => $req->user?->email ?? '',
                'status' => $req->status,
                'rejection_reason' => $req->rejection_reason,
                'reviewer_name' => $req->reviewer?->name,
                'created_at' => $req->created_at?->toIso8601String(),
            ]);
    }

    public function approveJoinRequest(TeamJoinRequest $joinRequest, User $reviewer): void
    {
        $this->authorizeReview($joinRequest, $reviewer);

        if ($joinRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending join requests can be approved.',
            ]);
        }

        $team = $joinRequest->team;
        $applicant = $joinRequest->user;

        if (! $team || ! $applicant) {
            throw ValidationException::withMessages([
                'request' => 'Team or user no longer exists.',
            ]);
        }

        if ($applicant->team_id !== null) {
            throw ValidationException::withMessages([
                'request' => 'User already belongs to a team.',
            ]);
        }

        if ($this->teams->hasReachedMaxMembers($team)) {
            throw ValidationException::withMessages([
                'request' => "This team has reached its maximum member limit ({$team->max_member}).",
            ]);
        }

        DB::transaction(function () use ($joinRequest, $applicant, $team, $reviewer) {
            $applicant->forceFill([
                'team_id' => $team->id,
                'is_team_leader' => false,
            ])->save();

            $joinRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            // Cancel any other pending join requests from this user
            TeamJoinRequest::where('user_id', $applicant->id)
                ->where('id', '!=', $joinRequest->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        });

        $this->markNotificationsAsRead($joinRequest);
        $this->notifyUsers(collect([$applicant]), 'join_team_approved', $reviewer, $team, $joinRequest->id);
    }

    public function rejectJoinRequest(TeamJoinRequest $joinRequest, User $reviewer, ?string $reason = null): void
    {
        $this->authorizeReview($joinRequest, $reviewer);

        if ($joinRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending join requests can be rejected.',
            ]);
        }

        $joinRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->markNotificationsAsRead($joinRequest);

        if ($joinRequest->user && $joinRequest->team) {
            $this->notifyUsers(
                collect([$joinRequest->user]),
                'join_team_rejected',
                $reviewer,
                $joinRequest->team,
                $joinRequest->id,
                $joinRequest->rejection_reason,
            );
        }
    }

    private function authorizeReview(TeamJoinRequest $joinRequest, User $reviewer): void
    {
        if ($reviewer->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $reviewer->isTeamLeader() && $joinRequest->team_id === $reviewer->team_id,
            403,
            'You are not allowed to review this join request.',
        );
    }

    private function markNotificationsAsRead(TeamJoinRequest $joinRequest): void
    {
        $ids = DB::table('notifications')
            ->whereNull('read_at')
            ->where('type', TeamMembershipNotification::class)
            ->get(['id', 'data'])
            ->filter(function ($row) use ($joinRequest) {
                $data = json_decode((string) $row->data, true);
                return (int) ($data['team_join_request_id'] ?? 0) === $joinRequest->id;
            })
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            DB::table('notifications')
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function notifyUsers(
        Collection $recipients,
        string $action,
        User $actor,
        Team $team,
        ?int $joinRequestId = null,
        ?string $reason = null,
    ): void {
        Notification::send(
            $recipients,
            new TeamMembershipNotification($action, $actor, $team, ['database'], $joinRequestId, $reason),
        );

        if (! SystemConfig::getBool('email_notif_team_leader_request', true)) {
            return;
        }

        $mailer = config('mail.default');
        if (! $mailer || in_array($mailer, ['log', 'array'], true)) {
            return;
        }

        $emailRecipients = $recipients->filter(fn (User $u) => (bool) ($u->email_notifications_enabled ?? true));
        if ($emailRecipients->isEmpty()) {
            return;
        }

        try {
            Notification::send(
                $emailRecipients,
                new TeamMembershipNotification($action, $actor, $team, ['mail'], $joinRequestId, $reason),
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to send team membership email notification.', [
                'action' => $action,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
