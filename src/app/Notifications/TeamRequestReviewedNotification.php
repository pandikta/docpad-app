<?php

namespace App\Notifications;

use App\Models\TeamRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamRequestReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TeamRequest $teamRequest,
        private readonly string $decision,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        if ($this->decision === 'approved') {
            return (new MailMessage)
                ->subject("[{$appName}] Your Team \"{$this->teamRequest->team_name}\" Is Approved!")
                ->greeting("Hello {$this->teamRequest->requester_name},")
                ->line("Great news! Your request to create team **{$this->teamRequest->team_name}** (`{$this->teamRequest->team_code}`) has been approved.")
                ->line('You have been assigned as the **Team Leader / PM** and your account is now active.')
                ->line('You can share your Team Code with up to 5 team members so they can register under your team.')
                ->action("Sign In to {$appName}", route('login'));
        }

        $mail = (new MailMessage)
            ->subject("[{$appName}] Update on Your Team Request \"{$this->teamRequest->team_name}\"")
            ->greeting("Hello {$this->teamRequest->requester_name},")
            ->line("Unfortunately, your request to create team **{$this->teamRequest->team_name}** (`{$this->teamRequest->team_code}`) was not approved.");

        if ($this->teamRequest->rejection_reason) {
            $mail->line("**Reason:** {$this->teamRequest->rejection_reason}");
        }

        return $mail->line('If you believe this was a mistake, please contact your administrator or submit a new request.');
    }
}
