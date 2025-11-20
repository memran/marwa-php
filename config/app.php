<?php
return [
    /**
     * Application name
     */
    'name' => env('APP_ENV', 'development'),
    /**
     * App Base URL
     */
    'base_path' => env('APP_URL', 'http://localhost/'),
    /**
     * Application Debug
     */
    'debug' => env('APP_DEBUG', false),
    /**
     * Application Security Key
     */
    'key' => env('APP_KEY', generate_key()),
    /**
     * Application Default Locale
     */
    'defaultLocale' => 'en',
    /**
     * language Translator Path
     */
    'langPath' => resources_path() . DIRECTORY_SEPARATOR . 'lang',
    /**
     * Log Enable
     */
    'log' => env('LOG_ENABLE', false),
    /**
     * Log Driver
     */
    'log_channel' => env('LOG_CHANNEL', 'file'),
    /**
     * List of Service providers
     */
    'providers' => [
        Marwa\Framework\Providers\KernalServiceProvider::class,
        // Only for CLI apps: Marwa\Framework\Providers\ConsoleServiceProvider::class,
    ],
    /**
     * List of Middlewares
     */
    'middlewares' => [
        Marwa\Framework\Middlewares\MaintenanceMiddleware::class,
        Marwa\Framework\Middlewares\RequestIdMiddleware::class,
        Marwa\Framework\Middlewares\DebugbarMiddleware::class,
        Marwa\Framework\Middlewares\RouterMiddleware::class
    ],
    /**
     *  Maintinenance Mode
     */
    'maintenance' => env('MAINTENANCE', 0),
    /**
     * Maintenance time
     */
    'maintenance_time' => env('MAINTENANCE_TIME', 300),
    /**
     * Debugbar Enable/Disable
     */
    'debugbar' => env('DEBUGBAR_ENABLED', false),
    /**
     *  Collectors for Debugbar
     */
    'collectors' => [
        Marwa\DebugBar\Collectors\TimelineCollector::class,
        Marwa\DebugBar\Collectors\MemoryCollector::class,
        Marwa\DebugBar\Collectors\PhpCollector::class,
        Marwa\DebugBar\Collectors\RequestCollector::class,
        Marwa\DebugBar\Collectors\KpiCollector::class,
        Marwa\DebugBar\Collectors\VarDumperCollector::class,
        Marwa\DebugBar\Collectors\LogCollector::class,
        Marwa\DebugBar\Collectors\DbQueryCollector::class,
        Marwa\DebugBar\Collectors\SessionCollector::class,
        Marwa\DebugBar\Collectors\ExceptionCollector::class
    ]
];
