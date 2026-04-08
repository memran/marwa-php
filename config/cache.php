<?php

declare(strict_types=1);

$normalize = static function (string $path): string {
    if ($path === '' || $path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    return base_path($path);
};

$sqlitePath = env('CACHE_SQLITE_PATH', cache_path('framework.sqlite'));

return [
    'enabled' => env('CACHE_ENABLED', true),
    'driver' => env('CACHE_DRIVER', extension_loaded('pdo_sqlite') ? 'sqlite' : 'memory'),
    'namespace' => env('CACHE_NAMESPACE', 'default'),
    'buffered' => env('CACHE_BUFFERED', true),
    'transactional' => env('CACHE_TRANSACTIONAL', false),
    'stampede' => [
        'enabled' => env('CACHE_STAMPEDE_ENABLED', false),
        'sla' => env('CACHE_STAMPEDE_SLA', 1000),
    ],
    'sqlite' => [
        'path' => $normalize(is_string($sqlitePath) && $sqlitePath !== '' ? $sqlitePath : cache_path('framework.sqlite')),
        'table' => env('CACHE_SQLITE_TABLE', 'framework_cache'),
    ],
    'memory' => [
        'limit' => env('CACHE_MEMORY_LIMIT', null),
    ],
];
