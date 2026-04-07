<?php

declare(strict_types=1);

$isDevelopment = in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true);

return [
    'viewsPath' => resources_path() . DIRECTORY_SEPARATOR . 'views',
    'cachePath' => $isDevelopment
        ? null
        : storage_path('cache') . DIRECTORY_SEPARATOR . 'views',
    'debug' => $isDevelopment,
    'defaultTheme' => 'default',
];
