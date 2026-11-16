<?php

namespace App\Notifications;

use App\Models\TeamRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamRequestSubmittedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly TeamRequest $teamRequest,
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
            'action' => 'team_request_submitted',
            'actor_id' => null,
            'actor_name' => $this->teamRequest->requester_name,
            'team_request_id' => $this->teamRequest->id,
            'team_code' => $this->teamRequest->team_code,
            'team_name' => $this->teamRequest->team_name,
            'document_id' => null,
            'document_name' => "{$this->teamRequest->team_name} ({$this->teamRequest->team_code})",
            'document_count' => 1,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        $mail = (new MailMessage)
            ->subject("[{$appName}] New Team Request: {$this->teamRequest->team_name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**{$this->teamRequest->requester_name}** ({$this->teamRequest->requester_email}) has submitted a request to create a new team.")
            ->line("**Team Name:** {$this->teamRequest->team_name}")
            ->line("**Team Code:** {$this->teamRequest->team_code}");

        if ($this->teamRequest->company_email) {
            $mail->line("**Company Email:** {$this->teamRequest->company_email}");
        }

        if ($this->teamRequest->description) {
            $mail->line("**Notes:** {$this->teamRequest->description}");
        }

        return $mail
            ->line('Approving this request will create the team (default max 5 members) and assign the requester as Team Leader / PM.')
            ->action('Review Team Requests', route('settings.teams.index'));
    }
}
