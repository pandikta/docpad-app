<?php

namespace App\Services;

use App\Models\SystemConfig;
use App\Models\Team;
use App\Models\TeamLeaderRequest;
use App\Models\User;
use App\Notifications\TeamLeaderRequestedNotification;
use App\Notifications\TeamLeaderReviewedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TeamLeaderRequestService
{
    /**
     * Get Team Leader request button/status state for the authenticated user.
     *
     * @return array{show_button: bool, has_pending_request: bool, member_count: int, auto_approve: bool}|null
     */
    public function getStatusForUser(?User $user): ?array
    {
        if (! $user || $user->isSuperAdmin() || ! $user->team_id || $user->isTeamLeader()) {
            return null;
        }

        $team = Team::find($user->team_id);
        $allowMultiple = (bool) ($team?->allow_multiple_leaders ?? false);

        $hasLeader = User::where('team_id', $user->team_id)
            ->where('is_team_leader', true)
            ->exists();

        if (! $allowMultiple && $hasLeader) {
            return [
                'show_button' => false,
                'has_pending_request' => false,
                'member_count' => 0,
                'auto_approve' => false,
            ];
        }

        $memberCount = User::where('team_id', $user->team_id)->count();
        $hasPending = Schema::hasTable('trx_team_leader_requests')
            ? TeamLeaderRequest::where('team_id', $user->team_id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->exists()
            : false;

        return [
            'show_button' => true,
            'has_pending_request' => $hasPending,
            'member_count' => $memberCount,
            'auto_approve' => $memberCount <= 1,
        ];
    }

    /**
     * Submit a request to become Team Leader. Auto-approves if the team only has 1 member.
     *
     * @return array{auto_approved: bool, request: TeamLeaderRequest}
     */
    public function requestLeadership(User $user): array
    {
        if ($user->isSuperAdmin() || ! $user->team_id) {
            throw ValidationException::withMessages([
                'team' => 'You must belong to a team to request Team Leader.',
            ]);
        }

        if ($user->isTeamLeader()) {
            throw ValidationException::withMessages([
                'team' => 'You are already a Team Leader.',
            ]);
        }

        $team = Team::findOrFail($user->team_id);

        $hasLeader = User::where('team_id', $team->id)
            ->where('is_team_leader', true)
            ->exists();

        if (! $team->allow_multiple_leaders && $hasLeader) {
            throw ValidationException::withMessages([
                'team' => 'This team already has a Team Leader.',
            ]);
        }

        $alreadyPending = TeamLeaderRequest::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            throw ValidationException::withMessages([
                'team' => 'You already have a pending Team Leader request.',
            ]);
        }

        $memberCount = User::where('team_id', $team->id)->count();

        if ($memberCount <= 1) {
            $leaderRequest = DB::transaction(function () use ($user, $team) {
                $user->forceFill(['is_team_leader' => true])->save();

                return TeamLeaderRequest::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'status' => 'approved',
                    'reviewed_at' => now(),
                ]);
            });

            return [
                'auto_approved' => true,
                'request' => $leaderRequest,
            ];
        }

        $leaderRequest = TeamLeaderRequest::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        // Notify all members of the team + all superadmins (deduplicated by user id)
        $recipients = User::where('team_id', $team->id)
            ->orWhere('is_superadmin', true)
            ->get()
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TeamLeaderRequestedNotification($leaderRequest, $user, $team, ['database']));
            $emailRecipients = $recipients->filter(fn (User $u) => (bool) ($u->email_notifications_enabled ?? true));
            if ($emailRecipients->isNotEmpty()) {
                $this->sendMailSafely(
                    fn () => Notification::send($emailRecipients, new TeamLeaderRequestedNotification($leaderRequest, $user, $team, ['mail'])),
                    ['team_leader_request_id' => $leaderRequest->id, 'event' => 'submitted'],
                );
            }
        }

        return [
            'auto_approved' => false,
            'request' => $leaderRequest,
        ];
    }

    /**
     * List all Team Leader requests for admin management (pending first, then newest).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listForManagement(): Collection
    {
        if (! Schema::hasTable('trx_team_leader_requests')) {
            return collect();
        }

        return TeamLeaderRequest::with([
            'team:id,team_code,team_name',
            'user:id,name,email',
            'reviewer:id,name',
        ])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TeamLeaderRequest $req) => [
                'id' => $req->id,
                'team_id' => $req->team_id,
                'team_code' => $req->team?->team_code ?? '',
                'team_name' => $req->team?->team_name ?? '',
                'user_id' => $req->user_id,
                'user_name' => $req->user?->name ?? '',
                'user_email' => $req->user?->email ?? '',
                'status' => $req->status,
                'rejection_reason' => $req->rejection_reason,
                'reviewer_name' => $req->reviewer?->name,
                'reviewed_at' => $req->reviewed_at?->toIso8601String(),
                'created_at' => $req->created_at?->toIso8601String(),
            ]);
    }

    public function approve(TeamLeaderRequest $leaderRequest, User $reviewer): void
    {
        if ($leaderRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be approved.',
            ]);
        }

        $team = $leaderRequest->team;
        $allowMultiple = (bool) ($team?->allow_multiple_leaders ?? false);

        $hasLeader = User::where('team_id', $leaderRequest->team_id)
            ->where('is_team_leader', true)
            ->exists();

        if (! $allowMultiple && $hasLeader) {
            throw ValidationException::withMessages([
                'request' => 'This team already has a Team Leader.',
            ]);
        }

        $otherPending = collect();

        DB::transaction(function () use ($leaderRequest, $reviewer, $allowMultiple, &$otherPending) {
            $leaderRequest->user?->forceFill(['is_team_leader' => true])->save();

            $leaderRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            if (! $allowMultiple) {
                $otherPending = TeamLeaderRequest::where('team_id', $leaderRequest->team_id)
                    ->where('id', '!=', $leaderRequest->id)
                    ->where('status', 'pending')
                    ->get();

                foreach ($otherPending as $pending) {
                    $pending->update([
                        'status' => 'rejected',
                        'rejection_reason' => 'Another member was approved as Team Leader.',
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now(),
                    ]);
                }
            }
        });

        $this->markNotificationsAsRead($leaderRequest);
        $this->notifyDecision($leaderRequest, $reviewer, 'approved');

        foreach ($otherPending as $rejected) {
            $this->markNotificationsAsRead($rejected);
            $this->notifyDecision($rejected, $reviewer, 'rejected');
        }
    }

    public function reject(TeamLeaderRequest $leaderRequest, User $reviewer, ?string $reason = null): void
    {
        if ($leaderRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be rejected.',
            ]);
        }

        $leaderRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->markNotificationsAsRead($leaderRequest);
        $this->notifyDecision($leaderRequest, $reviewer, 'rejected');
    }

    private function notifyDecision(TeamLeaderRequest $leaderRequest, User $reviewer, string $decision): void
    {
        $applicant = $leaderRequest->user;
        $team = $leaderRequest->team;

        if (! $applicant || ! $team) {
            return;
        }

        $applicant->notify(new TeamLeaderReviewedNotification($leaderRequest, $team, $reviewer, $decision, ['database']));

        if ($applicant->email_notifications_enabled ?? true) {
            $this->sendMailSafely(
                fn () => $applicant->notify(new TeamLeaderReviewedNotification($leaderRequest, $team, $reviewer, $decision, ['mail'])),
                ['team_leader_request_id' => $leaderRequest->id, 'decision' => $decision],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function sendMailSafely(callable $callback, array $context = []): void
    {
        if (! SystemConfig::getBool('email_notif_team_leader_request', true)) {
            return;
        }

        $mailer = config('mail.default');
        if (! $mailer || in_array($mailer, ['log', 'array'], true)) {
            return;
        }

        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning('Failed to send Team Leader email notification.', [
                ...$context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function markNotificationsAsRead(TeamLeaderRequest $leaderRequest): void
    {
        $ids = DB::table('notifications')
            ->whereNull('read_at')
            ->where('type', TeamLeaderRequestedNotification::class)
            ->get(['id', 'data'])
            ->filter(function ($row) use ($leaderRequest) {
                $data = json_decode((string) $row->data, true);
                return (int) ($data['team_leader_request_id'] ?? 0) === $leaderRequest->id;
            })
            ->pluck('id')
            ->all();

        if ($ids !== []) {
            DB::table('notifications')
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }
    }
}
