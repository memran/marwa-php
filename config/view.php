<?php

declare(strict_types=1);

return [
    'activeTheme' => env('FRONTEND_THEME', 'default'),
    'fallbackTheme' => env('FRONTEND_THEME', 'default'),
    'frontendTheme' => env('FRONTEND_THEME', 'default'),
    'adminTheme' => env('ADMIN_THEME', 'admin'),
    'debug' => env('APP_DEBUG', false),
    'cache' => [
        'enabled' => !in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true),
    ],
    'extensions' => [],
];
