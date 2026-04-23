<?php

declare(strict_types=1);

namespace App\Modules\Activity\Listeners;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Notifications\Events\NotificationDeleted;
use App\Modules\Users\Models\User;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\AbstractEventListener;

final class RecordNotificationDeletedActivityListener extends AbstractEventListener
{
    public function __construct(
        private readonly ActivityRecorder $recorder,
        private readonly AuthManager $auth,
    ) {}

    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof NotificationDeleted) {
            return;
        }

        $notification = $event->notification;

        $this->recorder->recordActorAction(
            'notification.deleted',
            'Deleted notification.',
            $this->actor(),
            'notification',
            (int) $notification->getKey(),
            [
                'state' => [
                    'user_id' => $notification->getAttribute('user_id'),
                    'type' => $notification->getAttribute('type'),
                    'title' => $notification->getAttribute('title'),
                    'message' => $notification->getAttribute('message'),
                    'action_url' => $notification->getAttribute('action_url'),
                    'is_read' => $notification->getAttribute('is_read'),
                ],
            ]
        );
    }

    private function actor(): ?User
    {
        return $this->auth->user() instanceof User ? $this->auth->user() : null;
    }
}
