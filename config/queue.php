<?php

declare(strict_types=1);

$queuePath = env('QUEUE_PATH', base_path('storage/queue'));

return [
    'enabled' => env('QUEUE_ENABLED', true),
    'default' => env('QUEUE_DEFAULT', 'default'),
    'path' => is_string($queuePath) && $queuePath !== '' ? $queuePath : base_path('storage/queue'),
    'retryAfter' => env('QUEUE_RETRY_AFTER', 90),
];
