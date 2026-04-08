<?php

declare(strict_types=1);

$normalize = static function (string $path): string {
    if ($path === '' || $path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    return base_path($path);
};

$databasePath = env('DB_DATABASE', 'database/database.sqlite');

return [
    'enabled' => env('DB_ENABLED', false),
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $normalize(is_string($databasePath) && $databasePath !== '' ? $databasePath : 'database/database.sqlite'),
            'debug' => debug(),
        ],
    ],
    'debug' => debug(),
    'useDebugPanel' => debug(),
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
