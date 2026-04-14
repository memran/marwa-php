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
use Marwa\Framework\Middlewares\MaintenanceMiddleware;
use Marwa\Framework\Middlewares\RequestIdMiddleware;
use Marwa\Framework\Middlewares\RouterMiddleware;
use Marwa\Framework\Middlewares\SecurityMiddleware;
use Marwa\Framework\Middlewares\SessionMiddleware;
use App\Http\Middleware\DebugbarMiddleware;

return [
    'name' => env('APP_NAME', 'MarwaPHP'),
    'debugbar' => (bool) env('DEBUGBAR_ENABLED', 
        (bool) env('APP_DEBUG', false) 
        && !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
    ),
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
    'providers' => [
        Marwa\Framework\Providers\KernalServiceProvider::class,
    ],
    'maintenance' => [
        'template' => 'maintenance.twig',
        'message' => 'Service temporarily unavailable for maintenance',
    ],
    'error404' => [
        'template' => 'errors/404.twig',
    ],
    'middlewares' => [
        RequestIdMiddleware::class,
        SessionMiddleware::class,
        MaintenanceMiddleware::class,
        SecurityMiddleware::class,
        DebugbarMiddleware::class,
        RouterMiddleware::class

    ],
];
