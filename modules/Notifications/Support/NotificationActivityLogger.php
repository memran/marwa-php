<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Notifications\Models\Notification;

final class NotificationActivityLogger
{
    public function notificationCreated(Notification $notification): void
    {
        $this->record('notification.created', 'Created notification.', $notification);
    }

    public function notificationDeleted(Notification $notification): void
    {
        $this->record('notification.deleted', 'Deleted notification.', $notification);
    }

    private function record(string $action, string $description, Notification $notification): void
    {
        event(new ActivityRecordingRequested(
            $action,
            $description,
            'notification',
            (int) $notification->getKey(),
            ['state' => $this->state($notification)]
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function state(Notification $notification): array
    {
        return [
            'user_id' => $notification->getAttribute('user_id'),
            'type' => $notification->getAttribute('type'),
            'title' => $notification->getAttribute('title'),
            'message' => $notification->getAttribute('message'),
            'action_url' => $notification->getAttribute('action_url'),
            'is_read' => $notification->getAttribute('is_read'),
        ];
    }
}
