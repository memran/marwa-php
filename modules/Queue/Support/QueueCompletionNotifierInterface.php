<?php

declare(strict_types=1);

namespace App\Modules\Queue\Support;

use Marwa\Framework\Queue\QueuedJob;

interface QueueCompletionNotifierInterface
{
    public function notifyCompleted(QueuedJob $job, int $result): void;

    public function notifyFailed(QueuedJob $job, string $message): void;
}
