<?php

declare(strict_types=1);

namespace App\Modules\Activity\Listeners;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Activity\Models\Activity;
use App\Modules\Activity\Support\ActivityPayload;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\AbstractEventListener;

final class RecordActivityRecordingListener extends AbstractEventListener
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof ActivityRecordingRequested) {
            return;
        }

        try {
            Activity::create(ActivityPayload::actorAction(
                $event->action,
                $event->description,
                $this->actor(),
                $event->subjectType,
                $event->subjectId,
                $event->details
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
