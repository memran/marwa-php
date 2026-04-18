<?php

declare(strict_types=1);

return [
    'enable' => (bool) env('LOG_ENABLED', true),
    'filter' => [],
    'storage' => [
        'driver' => env('LOG_CHANNEL', 'file'),
        'path' => storage_path('logs'),
        'prefix' => env('LOG_PREFIX', 'marwa'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
];
