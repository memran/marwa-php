<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('DB_ENABLED', true),
    'default' => env('DB_CONNECTION', 'sqlite'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path('database/database.sqlite'),
            'debug' => (bool) env('APP_DEBUG', false),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => (string) env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => (string) env('DB_NAME', 'marwa'),
            'username' => (string) env('DB_USER', 'root'),
            'password' => (string) env('DB_PASSWORD', ''),
            'charset' => (string) env('DB_CHARSET', 'utf8mb4'),
            'debug' => (bool) env('APP_DEBUG', false),
        ],
    ],
    'debug' => (bool) env('APP_DEBUG', false),
    'useDebugPanel' => (bool) env('APP_DEBUG', false),
    'migrationsPath' => base_path('database/migrations'),
    'seedersPath' => base_path('database/seeders'),
    'seedersNamespace' => 'Database\\Seeders',
];
