<?php

declare(strict_types=1);

use Marwa\DebugBar\Collectors\CacheCollector;
use Marwa\DebugBar\Collectors\DbQueryCollector;
use Marwa\DebugBar\Collectors\ExceptionCollector;
use Marwa\DebugBar\Collectors\KpiCollector;
use Marwa\DebugBar\Collectors\LogCollector;
use Marwa\DebugBar\Collectors\MemoryCollector;
use Marwa\DebugBar\Collectors\PhpCollector;
use Marwa\DebugBar\Collectors\RequestCollector;
use Marwa\DebugBar\Collectors\SessionCollector;
use Marwa\DebugBar\Collectors\TimelineCollector;
use Marwa\DebugBar\Collectors\VarDumperCollector;

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'title' => env('APP_TITLE', env('APP_NAME', 'MarwaPHP')),
    'base_path' => env('APP_URL', 'http://localhost/'),
    'debug' => env('APP_DEBUG', false),
    'debugbar' => env('APP_DEBUG', false),
    'collectors' => [
        RequestCollector::class,
        DbQueryCollector::class,
        MemoryCollector::class,
        LogCollector::class,
        SessionCollector::class,
        PhpCollector::class,
        TimelineCollector::class,
        VarDumperCollector::class,
        ExceptionCollector::class,
        CacheCollector::class,
        KpiCollector::class,
    ],
    'key' => env('APP_KEY', generate_key()),
    'defaultLocale' => 'en',
    'langPath' => resources_path() . DIRECTORY_SEPARATOR . 'lang',
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
