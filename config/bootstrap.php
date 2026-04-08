<?php

declare(strict_types=1);

$normalize = static function (string $path): string {
    if ($path === '' || $path[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
        return $path;
    }

    return base_path($path);
};

$configCache = env('APP_CONFIG_CACHE', cache_path('config.php'));
$routeCache = env('APP_ROUTE_CACHE', cache_path('routes.php'));
$moduleCache = env('APP_MODULE_CACHE', cache_path('modules.php'));

return [
    'configCache' => $normalize(is_string($configCache) && $configCache !== '' ? $configCache : cache_path('config.php')),
    'routeCache' => $normalize(is_string($routeCache) && $routeCache !== '' ? $routeCache : cache_path('routes.php')),
    'moduleCache' => $normalize(is_string($moduleCache) && $moduleCache !== '' ? $moduleCache : cache_path('modules.php')),
];
