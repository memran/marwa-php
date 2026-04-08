<?php

declare(strict_types=1);

return [
    'viewsPath' => resources_path() . DIRECTORY_SEPARATOR . 'views',
    'cachePath' => in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true)
        ? null
        : cache_path('views'),
    'debug' => in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true),
    'frontendTheme' => env('FRONTEND_THEME', 'default'),
    'adminTheme' => env('ADMIN_THEME', 'admin'),
    'defaultTheme' => env('FRONTEND_THEME', 'default'),
];
