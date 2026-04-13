<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\Users\Models\User;
use Marwa\Framework\Adapters\Event\AbstractEvent;

final class ActivityRecordRequested extends AbstractEvent
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly string $action,
        public readonly string $description,
        public readonly ?User $actor = null,
        public readonly ?string $subjectType = null,
        public readonly ?int $subjectId = null,
        public readonly array $details = []
    ) {
        parent::__construct();
    }
}
