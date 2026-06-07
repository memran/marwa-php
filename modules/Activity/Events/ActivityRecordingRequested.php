<?php

declare(strict_types=1);

namespace App\Modules\Activity\Events;

use Marwa\Framework\Adapters\Event\NamedEvent;

final class ActivityRecordingRequested extends NamedEvent
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly string $action,
        public readonly string $description,
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly array $details = [],
    ) {
        parent::__construct('activity.recording.requested', [
            'action' => $this->action,
            'description' => $this->description,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'details' => $this->details,
        ]);
    }
}
