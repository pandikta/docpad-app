<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamRequest;
use App\Models\User;
use App\Notifications\TeamRequestSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class TeamRequestService
{
    public function __construct(
        private readonly TeamService $teams,
        private readonly PasswordHistoryService $passwordHistory,
    ) {}

    /**
     * Submit a new team creation request from a guest requester.
     *
     * @param  array{team_code: string, team_name: string, max_member?: int, description?: ?string, requester_name: string, requester_email: string, password: string}  $data
     */
    public function submit(array $data): TeamRequest
    {
        $teamCode = trim($data['team_code']);
        $email = strtolower(trim($data['requester_email']));

        if (Team::where('team_code', $teamCode)->exists()
            || TeamRequest::where('team_code', $teamCode)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'team_code' => 'This team code is already in use or pending approval.',
            ]);
        }

        if (User::where('email', $email)->exists()
            || TeamRequest::where('requester_email', $email)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'requester_email' => 'This email is already registered or has a pending team request.',
            ]);
        }

        $teamRequest = TeamRequest::create([
            'team_code' => $teamCode,
            'team_name' => trim($data['team_name']),
            'max_member' => 5,
            'description' => isset($data['description']) && trim((string) $data['description']) !== ''
                ? trim((string) $data['description'])
                : null,
            'company_email' => isset($data['company_email']) && trim((string) $data['company_email']) !== ''
                ? strtolower(trim((string) $data['company_email']))
                : null,
            'requester_name' => trim($data['requester_name']),
            'requester_email' => $email,
            'password' => Hash::make($data['password']),
            'status' => 'pending',
        ]);

        $this->notifySuperadmins($teamRequest);

        return $teamRequest;
    }

    /**
     * Notify all superadmin users via in-app database notification and (if configured) email.
     */
    private function notifySuperadmins(TeamRequest $teamRequest): void
    {
        $superadmins = User::where('is_superadmin', true)->get();

        if ($superadmins->isEmpty()) {
            return;
        }

        // Always deliver in-app notification
        Notification::send($superadmins, new TeamRequestSubmittedNotification($teamRequest, ['database']));

        // Attempt email notification safely so missing/unconfigured SMTP never blocks submission
        $mailer = config('mail.default');
        if ($mailer && ! in_array($mailer, ['log', 'array'], true)) {
            try {
                Notification::send($superadmins, new TeamRequestSubmittedNotification($teamRequest, ['mail']));
            } catch (\Throwable $e) {
                Log::warning('Failed to send team request email notification to superadmins.', [
                    'team_request_id' => $teamRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * List all team requests for admin management (pending first, then newest).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listForManagement(): Collection
    {
        return TeamRequest::with(['reviewer:id,name', 'createdTeam:id,team_code,team_name'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TeamRequest $req) => [
                'id' => $req->id,
                'team_code' => $req->team_code,
                'team_name' => $req->team_name,
                'max_member' => $req->max_member,
                'description' => $req->description,
                'company_email' => $req->company_email,
                'requester_name' => $req->requester_name,
                'requester_email' => $req->requester_email,
                'status' => $req->status,
                'rejection_reason' => $req->rejection_reason,
                'reviewer_name' => $req->reviewer?->name,
                'reviewed_at' => $req->reviewed_at?->toIso8601String(),
                'created_at' => $req->created_at?->toIso8601String(),
            ]);
    }

    /**
     * Approve a pending team request: create the team and register the requester as Team Leader / PM.
     */
    public function approve(TeamRequest $teamRequest, User $reviewer): Team
    {
        if ($teamRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be approved.',
            ]);
        }

        if (Team::where('team_code', $teamRequest->team_code)->exists()) {
            throw ValidationException::withMessages([
                'request' => "Team code {$teamRequest->team_code} already exists.",
            ]);
        }

        if (User::where('email', $teamRequest->requester_email)->exists()) {
            throw ValidationException::withMessages([
                'request' => "Email {$teamRequest->requester_email} is already registered.",
            ]);
        }

        return DB::transaction(function () use ($teamRequest, $reviewer) {
            $team = $this->teams->create([
                'team_code' => $teamRequest->team_code,
                'team_name' => $teamRequest->team_name,
                'max_member' => $teamRequest->max_member,
                'description' => $teamRequest->description,
                'is_active' => true,
            ]);

            $leader = User::make([
                'team_id' => $team->id,
                'name' => $teamRequest->requester_name,
                'email' => $teamRequest->requester_email,
                'password' => $teamRequest->password,
                'storage_limit_mb' => 100,
            ])->forceFill([
                'is_team_leader' => true,
            ]);
            $leader->save();

            $this->passwordHistory->record($leader, $leader->password);

            $teamRequest->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'created_team_id' => $team->id,
            ]);

            return $team;
        });
    }

    /**
     * Reject a pending team request.
     */
    public function reject(TeamRequest $teamRequest, User $reviewer, ?string $reason = null): TeamRequest
    {
        if ($teamRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'Only pending requests can be rejected.',
            ]);
        }

        $teamRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $teamRequest;
    }
}
