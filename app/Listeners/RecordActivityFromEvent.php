<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ActivityRecordRequested;
use App\Modules\Activity\Support\ActivityRecorder;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\AbstractEventListener;

final class RecordActivityFromEvent extends AbstractEventListener
{
    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof ActivityRecordRequested) {
            return;
        }

        (new ActivityRecorder())->recordActorAction(
            $event->action,
            $event->description,
            $event->actor,
            $event->subjectType,
            $event->subjectId,
            $event->details
        );
    }
}
