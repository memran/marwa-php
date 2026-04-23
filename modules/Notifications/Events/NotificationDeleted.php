<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Events;

use App\Modules\Notifications\Models\Notification;
use Marwa\Framework\Adapters\Event\NamedEvent;

final class NotificationDeleted extends NamedEvent
{
    public function __construct(
        public readonly Notification $notification
    ) {
        parent::__construct('notification.deleted', [
            'id' => $notification->getKey(),
            'user_id' => $notification->getAttribute('user_id'),
            'type' => $notification->getAttribute('type'),
            'title' => $notification->getAttribute('title'),
            'message' => $notification->getAttribute('message'),
            'action_url' => $notification->getAttribute('action_url'),
            'is_read' => $notification->getAttribute('is_read'),
        ]);
    }
}
