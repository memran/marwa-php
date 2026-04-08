<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'title' => env('APP_TITLE', env('APP_NAME', 'MarwaPHP')),
    'base_path' => env('APP_URL', 'http://localhost/'),
    'debug' => env('APP_DEBUG', false),
    'debugbar' => env('APP_DEBUG', false),
    'collectors' => [],
    'key' => env('APP_KEY', generate_key()),
    'defaultLocale' => 'en',
    'langPath' => resources_path() . DIRECTORY_SEPARATOR . 'lang',
    'log' => env('LOG_ENABLE', false),
    'log_channel' => env('LOG_CHANNEL', 'file'),
    'providers' => [
        Marwa\Framework\Providers\KernalServiceProvider::class,
    ],
    'middlewares' => [
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\SessionMiddleware::class,
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\SecurityMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class,
    ],
    'maintenance' => env('MAINTENANCE', env('MAINTAINANCE', false)),
    'maintenance_time' => env('MAINTENANCE_TIME', 300),
];
