<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\TeamLeaderRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamLeaderRequestedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly TeamLeaderRequest $leaderRequest,
        private readonly User $requester,
        private readonly Team $team,
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
            'action' => 'team_leader_requested',
            'actor_id' => $this->requester->id,
            'actor_name' => $this->requester->name,
            'team_leader_request_id' => $this->leaderRequest->id,
            'team_id' => $this->team->id,
            'team_name' => $this->team->team_name,
            'document_id' => null,
            'document_name' => $this->team->team_name,
            'document_count' => 1,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        $mail = (new MailMessage)
            ->subject("[{$appName}] Team Leader Request for {$this->team->team_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->requester->name}** ({$this->requester->email}) has requested to become the **Team Leader** for team **{$this->team->team_name}**.");

        if ($notifiable instanceof User && $notifiable->isSuperAdmin()) {
            return $mail
                ->line('Please review and approve or reject this request in Master Teams.')
                ->action('Review Leader Requests', route('settings.teams.index'));
        }

        return $mail
            ->line('An administrator will review and approve this request shortly.')
            ->action("Open {$appName}", route('dashboard'));
    }
}
