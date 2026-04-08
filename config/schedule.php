<?php

declare(strict_types=1);

$lockPath = env('SCHEDULE_LOCK_PATH', base_path('storage/framework/schedule'));
$filePath = env('SCHEDULE_FILE_PATH', base_path('storage/framework/schedule'));

return [
    'enabled' => env('SCHEDULE_ENABLED', true),
    'driver' => env('SCHEDULE_DRIVER', 'file'),
    'lockPath' => is_string($lockPath) && $lockPath !== '' ? $lockPath : base_path('storage/framework/schedule'),
    'file' => [
        'path' => is_string($filePath) && $filePath !== '' ? $filePath : base_path('storage/framework/schedule'),
    ],
    'cache' => [
        'namespace' => env('SCHEDULE_CACHE_NAMESPACE', 'schedule'),
    ],
    'database' => [
        'connection' => env('SCHEDULE_DB_CONNECTION', 'sqlite'),
        'table' => env('SCHEDULE_DB_TABLE', 'schedule_jobs'),
    ],
    'defaultLoopSeconds' => env('SCHEDULE_DEFAULT_LOOP_SECONDS', 1),
    'defaultSleepSeconds' => env('SCHEDULE_DEFAULT_SLEEP_SECONDS', 1),
];
