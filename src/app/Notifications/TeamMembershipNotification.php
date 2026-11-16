<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMembershipNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly string $action,
        private readonly User $actor,
        private readonly Team $team,
        private readonly array $channels = ['database'],
        private readonly ?int $joinRequestId = null,
        private readonly ?string $reason = null,
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
            'action' => $this->action,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'team_id' => $this->team->id,
            'team_name' => $this->team->team_name,
            'team_join_request_id' => $this->joinRequestId,
            'rejection_reason' => $this->reason,
            'document_id' => null,
            'document_name' => $this->team->team_name,
            'document_count' => 1,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        if ($this->action === 'removed_from_team') {
            return (new MailMessage)
                ->subject("[{$appName}] Team Membership Update ({$this->team->team_name})")
                ->greeting("Hello {$notifiable->name},")
                ->line("You have been removed from team **{$this->team->team_name}** by **{$this->actor->name}**.")
                ->line('Your private documents remain safe. You can join another team anytime from your Dashboard.')
                ->action("Open {$appName}", route('dashboard'));
        }

        if ($this->action === 'join_team_requested') {
            return (new MailMessage)
                ->subject("[{$appName}] New Join Team Request ({$this->team->team_name})")
                ->greeting("Hello {$notifiable->name},")
                ->line("**{$this->actor->name}** ({$this->actor->email}) has requested to join team **{$this->team->team_name}**.")
                ->action('Review in Team Control', route('team-control.index'));
        }

        if ($this->action === 'join_team_approved') {
            return (new MailMessage)
                ->subject("[{$appName}] Welcome to {$this->team->team_name}!")
                ->greeting("Hello {$notifiable->name},")
                ->line("Your request to join team **{$this->team->team_name}** has been approved by **{$this->actor->name}**.")
                ->action("Open {$appName}", route('dashboard'));
        }

        $mail = (new MailMessage)
            ->subject("[{$appName}] Join Team Request Update ({$this->team->team_name})")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your request to join team **{$this->team->team_name}** was not approved by **{$this->actor->name}**.");

        if ($this->reason) {
            $mail->line("**Reason:** {$this->reason}");
        }

        return $mail->action("Open {$appName}", route('dashboard'));
    }
}
