<?php

declare(strict_types=1);

$normalize = static function (string $path): string {
    if ($path === '' || $path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    return base_path($path);
};

$databasePath = env('DB_DATABASE', 'database/database.sqlite');
$dbHost = env('DB_HOST', '127.0.0.1');
$dbPort = (int) env('DB_PORT', 3306);
$dbUser = env('DB_USERNAME', env('DB_USER', 'root'));
$dbPassword = (string) env('DB_PASSWORD', '');
$dbCharset = env('DB_CHARSET', 'utf8mb4');

return [
    'enabled' => env('DB_ENABLED', true),
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $normalize(is_string($databasePath) && $databasePath !== '' ? $databasePath : 'database/database.sqlite'),
            'debug' => env('APP_DEBUG', false),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => (string) $dbHost,
            'port' => $dbPort,
            'database' => env('DB_NAME', env('DB_DATABASE', 'marwa')),
            'username' => (string) $dbUser,
            'password' => $dbPassword,
            'charset' => (string) $dbCharset,
            'debug' => env('APP_DEBUG', false),
        ],
    ],
    'debug' => env('APP_DEBUG', false),
    'useDebugPanel' => env('APP_DEBUG', false),
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
