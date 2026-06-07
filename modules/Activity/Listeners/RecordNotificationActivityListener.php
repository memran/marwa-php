<?php

declare(strict_types=1);

namespace App\Modules\Activity\Listeners;

use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Support\ActivityPayload;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Notifications\Events\NotificationCreated;
use App\Modules\Users\Models\User;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\AbstractEventListener;

final class RecordNotificationActivityListener extends AbstractEventListener
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof NotificationCreated) {
            return;
        }

        $notification = $event->notification;

        try {
            Activity::create(ActivityPayload::actorAction(
                'notification.created',
                'Created notification.',
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
            ));
        } catch (\Throwable) {
            return;
        }
    }

    private function actor(): ?User
    {
        return $this->auth->user() instanceof User ? $this->auth->user() : null;
    }
}
