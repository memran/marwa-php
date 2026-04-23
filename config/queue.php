<?php

declare(strict_types=1);

$queuePath = env('QUEUE_PATH', base_path('storage/queue'));
$queueDatabaseConnection = env('QUEUE_DB_CONNECTION', env('DB_CONNECTION', 'default'));
$queueDatabaseTable = env('QUEUE_DB_TABLE', 'queue_jobs');
$queueDriver = env('QUEUE_DRIVER', 'database');

return [
    'enabled' => env('QUEUE_ENABLED', true),
    'driver' => is_string($queueDriver) && $queueDriver !== '' ? $queueDriver : 'database',
    'default' => env('QUEUE_DEFAULT', 'default'),
    'file' => [
        'path' => is_string($queuePath) && $queuePath !== '' ? $queuePath : base_path('storage/queue'),
    ],
    'database' => [
        'connection' => is_string($queueDatabaseConnection) && $queueDatabaseConnection !== ''
            ? $queueDatabaseConnection
            : 'default',
        'table' => is_string($queueDatabaseTable) && $queueDatabaseTable !== ''
            ? $queueDatabaseTable
            : 'queue_jobs',
    ],
    'retryAfter' => env('QUEUE_RETRY_AFTER', 90),
];
