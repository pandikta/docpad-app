<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordChangedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        private readonly int $actorId,
        private readonly string $actorName,
        private readonly array $channels = ['database'],
        private readonly ?string $temporaryPassword = null,
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
            'action' => 'password_changed_by_admin',
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'document_id' => null,
            'document_name' => '',
            'document_count' => 1,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'DocPad');

        $mail = (new MailMessage)
            ->subject("Your {$appName} Password Was Updated")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your {$appName} account password was reset by administrator **{$this->actorName}**.");

        if ($this->temporaryPassword !== null) {
            $mail->line("Your new temporary password is: **{$this->temporaryPassword}**")
                 ->line('Please sign in and change your password immediately from your Profile page.');
        } else {
            $mail->line('If you did not request this change, please contact your administrator immediately.');
        }

        return $mail->action("Sign In to {$appName}", route('login'));
    }
}
