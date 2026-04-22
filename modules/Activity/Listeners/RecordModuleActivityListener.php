<?php

declare(strict_types=1);

namespace App\Modules\Activity\Listeners;

use App\Modules\Activity\Support\AdminActivityTrail;
use Marwa\Framework\Adapters\Event\AbstractEvent;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Marwa\Framework\Adapters\Event\AbstractEventListener;

final class RecordModuleActivityListener extends AbstractEventListener
{
    public function handle(AbstractEvent $event): void
    {
        if (!$event instanceof RequestHandled) {
            return;
        }

        app(AdminActivityTrail::class)->record($event);
    }
}
