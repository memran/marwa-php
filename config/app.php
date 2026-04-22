<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'providers' => [
        Marwa\Framework\Providers\KernelServiceProvider::class,
        App\Providers\AdminNavigationServiceProvider::class,
    ],
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        App\Http\Middleware\NormalizeTrailingSlashMiddleware::class,
        App\Http\Middleware\ApplicationLifecycleMiddleware::class,
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
        Marwa\Framework\Middlewares\DebugbarMiddleware::class,
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
