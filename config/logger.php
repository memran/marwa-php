<?php

declare(strict_types=1);

return [
    'enable' => env('LOG_ENABLE', env('APP_ENV', 'production') !== 'production'),
    'filter' => [
        'password',
        'token',
        'authorization',
        'cookie',
        'secret',
    ],
    'storage' => [
        'driver' => env('LOG_CHANNEL', 'file'),
        'path' => storage_path('logs'),
        'prefix' => env('LOG_PREFIX', 'marwa-php'),
        'max_bytes' => '10MB',
        'level' => env('LOG_LEVEL', 'debug'),
    ],
];
