<?php

declare(strict_types=1);

$isDevelopment = in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true);
$frontendTheme = (string) env('FRONTEND_THEME', 'default');
$adminTheme = (string) env('ADMIN_THEME', $frontendTheme);

return [
    'viewsPath' => resources_path() . DIRECTORY_SEPARATOR . 'views',
    'cachePath' => $isDevelopment
        ? null
        : cache_path('views'),
    'debug' => $isDevelopment,
    'frontendTheme' => $frontendTheme,
    'adminTheme' => $adminTheme,
    'defaultTheme' => $frontendTheme,
];
