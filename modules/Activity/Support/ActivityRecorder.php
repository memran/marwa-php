<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Activity\Models\Activity;
use App\Modules\Users\Models\User;

final class ActivityRecorder
{
    /**
     * @param array<string, mixed> $details
     */
    public function recordActorAction(
        string $action,
        string $description,
        ?User $actor = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $details = []
    ): ?Activity {
        try {
            return Activity::create(ActivityPayload::actorAction(
                $action,
                $description,
                $actor,
                $subjectType,
                $subjectId,
                $details
            ));
        } catch (\Throwable) {
            return null;
        }
    }
}
