<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
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
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $broker = config('auth.defaults.passwords', 'users');
        $expireMinutes = (int) config("auth.passwords.{$broker}.expire", 60);
        $appName = config('app.name', 'DocPad');

        return (new MailMessage)
            ->subject("Reset Your {$appName} Password")
            ->view('emails.reset-password', [
                'appName' => $appName,
                'userName' => $notifiable->name ?? 'there',
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
