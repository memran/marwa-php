<?php

declare(strict_types=1);

return [
    'enabled' => env('CACHE_ENABLED', true),
    'driver' => env('CACHE_DRIVER', 'file'),
    'namespace' => env('CACHE_NAMESPACE', 'marwa'),
    'buffered' => env('CACHE_BUFFERED', false),
    'transactional' => env('CACHE_TRANSACTIONAL', false),

    'stampede' => [
        'enabled' => env('CACHE_STAMPEDE_ENABLED', false),
        'sla' => env('CACHE_STAMPEDE_SLA', 1000),
    ],

    'file' => [
        'path' => env('CACHE_FILE_PATH', storage_path('cache/framework')),
    ],

    'sqlite' => [
        'path' => env('CACHE_SQLITE_PATH', storage_path('cache/framework.sqlite')),
        'table' => env('CACHE_SQLITE_TABLE', 'framework_cache'),
    ],

    'memory' => [
        'limit' => env('CACHE_MEMORY_LIMIT', null),
    ],
];
