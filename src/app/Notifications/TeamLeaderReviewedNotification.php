<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\TeamLeaderRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamLeaderReviewedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly TeamLeaderRequest $leaderRequest,
        private readonly Team $team,
        private readonly User $reviewer,
        private readonly string $decision,
        private readonly array $channels = ['database'],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => $this->decision === 'approved' ? 'team_leader_approved' : 'team_leader_rejected',
            'actor_id' => $this->reviewer->id,
            'actor_name' => $this->reviewer->name,
            'team_leader_request_id' => $this->leaderRequest->id,
            'team_id' => $this->team->id,
            'team_name' => $this->team->team_name,
            'rejection_reason' => $this->leaderRequest->rejection_reason,
            'document_id' => null,
            'document_name' => $this->team->team_name,
            'document_count' => 1,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        if ($this->decision === 'approved') {
            return (new MailMessage)
                ->subject("[{$appName}] Team Leader Request Approved ({$this->team->team_name})")
                ->greeting("Hello {$notifiable->name},")
                ->line("Your request to become **Team Leader** for **{$this->team->team_name}** has been approved by **{$this->reviewer->name}**.")
                ->action("Open {$appName}", route('dashboard'));
        }

        $mail = (new MailMessage)
            ->subject("[{$appName}] Team Leader Request Update ({$this->team->team_name})")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request to become Team Leader for **{$this->team->team_name}** was not approved by **{$this->reviewer->name}**.");

        if ($this->leaderRequest->rejection_reason) {
            $mail->line("**Reason:** {$this->leaderRequest->rejection_reason}");
        }

        return $mail->action("Open {$appName}", route('dashboard'));
    }
}
