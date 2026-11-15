<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentCreatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  'created'|'uploaded'|'shared'|'revoked'  $action
     */
    public function __construct(
        private readonly string $action,
        private readonly int $actorId,
        private readonly string $actorName,
        private readonly ?int $documentId,
        private readonly string $documentName,
        private readonly int $documentCount = 1,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => $this->action,
            'actor_id' => $this->actorId,
            'actor_name' => $this->actorName,
            'document_id' => $this->documentId,
            'document_name' => $this->documentName,
            'document_count' => $this->documentCount,
        ];
    }
}
