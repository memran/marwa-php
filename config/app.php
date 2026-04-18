<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'providers' => [
        App\Providers\AdminNavigationServiceProvider::class,
    ],
    'debugbar' => (bool) env(
        'DEBUGBAR_ENABLED',
        (bool) env('APP_DEBUG', false)
            && !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
    ),
    'maintenance' => [
        'template' => 'maintenance.twig',
        'message' => 'Service temporarily unavailable for maintenance',
    ],
    'error404' => [
        'template' => 'errors/404.twig',
    ],
];
