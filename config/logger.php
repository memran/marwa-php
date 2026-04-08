<?php

declare(strict_types=1);

return [
    'enable' => env('LOG_ENABLE', env('APP_ENV', 'production') !== 'production'),
    'filter' => [
        'password',
        'token',
        'authorization',
    ],
    'storage' => [
        'driver' => env('LOG_CHANNEL', 'file'),
        'path' => storage_path() . DIRECTORY_SEPARATOR . 'logs',
        'prefix' => 'myapp',
        'max_bytes' => '10MB',
        'level' => env('LOG_LEVEL', 'debug'),
    ],
];
