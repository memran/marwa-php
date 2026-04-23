<?php

declare(strict_types=1);

namespace App\Modules\Queue\Support;

use App\Modules\Notifications\Support\NotificationService;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;

final class QueueCompletionNotifier implements QueueCompletionNotifierInterface
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function notifyCompleted(QueuedJob $job, int $result): void
    {
        $title = $job->name() === MailJob::NAME
            ? 'Queued mail completed'
            : 'Queued job completed';

        $message = sprintf(
            'Job [%s] finished successfully with result [%d].',
            $job->name(),
            $result
        );

        $this->notifications->sendToAdmins(
            'success',
            $title,
            $message,
            '/admin/queue/' . rawurlencode($job->id())
        );
    }

    public function notifyFailed(QueuedJob $job, string $message): void
    {
        $title = $job->name() === MailJob::NAME
            ? 'Queued mail failed'
            : 'Queued job failed';

        $body = trim($message);
        if ($body === '') {
            $body = 'The worker reported an unknown error.';
        }

        $this->notifications->sendToAdmins(
            'error',
            $title,
            sprintf('Job [%s] failed: %s', $job->name(), $body),
            '/admin/queue/' . rawurlencode($job->id())
        );
    }
}
