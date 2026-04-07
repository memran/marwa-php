<?php

declare(strict_types=1);

return [
    'enabled' => env('MODULES_ENABLED', false),
    'paths' => [],
    'cache' => (function (): string {
        $cache = (string) env('APP_MODULE_CACHE', cache_path('modules.php'));

        if ($cache !== '' && $cache[0] !== DIRECTORY_SEPARATOR && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $cache)) {
            return base_path($cache);
        }

        return $cache;
    })(),
    'forceRefresh' => in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true),
    'commandPaths' => [
        'Console/Commands',
    ],
    'commandConventions' => [
        'Console/Commands',
    ],
];
