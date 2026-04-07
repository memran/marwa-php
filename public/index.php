<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;

$app = new Application(dirname(__DIR__));

$isDebugEnvironment = in_array(env('APP_ENV', 'production'), ['local', 'development'], true);
$_ENV['APP_CONFIG_CACHE'] = $app->basePath('storage/cache/config.php');
$_SERVER['APP_CONFIG_CACHE'] = $_ENV['APP_CONFIG_CACHE'];
putenv('APP_CONFIG_CACHE=' . $_ENV['APP_CONFIG_CACHE']);
$_ENV['APP_ROUTE_CACHE'] = $app->basePath('storage/cache/routes.php');
$_SERVER['APP_ROUTE_CACHE'] = $_ENV['APP_ROUTE_CACHE'];
putenv('APP_ROUTE_CACHE=' . $_ENV['APP_ROUTE_CACHE']);
$_ENV['APP_MODULE_CACHE'] = $app->basePath('storage/cache/modules.php');
$_SERVER['APP_MODULE_CACHE'] = $_ENV['APP_MODULE_CACHE'];
putenv('APP_MODULE_CACHE=' . $_ENV['APP_MODULE_CACHE']);
$_ENV['APP_DEBUG'] = $isDebugEnvironment ? '1' : '0';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'];
putenv('APP_DEBUG=' . $_ENV['APP_DEBUG']);
$_ENV['DEBUGBAR_ENABLED'] = $isDebugEnvironment ? '1' : '0';
$_SERVER['DEBUGBAR_ENABLED'] = $_ENV['DEBUGBAR_ENABLED'];
putenv('DEBUGBAR_ENABLED=' . $_ENV['DEBUGBAR_ENABLED']);

$app->boot();

$http = $app->make(HttpRequestFactory::class);
$request = $http->request();

$kernel = $app->make(HttpKernel::class);
$response = $kernel->handle($request);
$kernel->terminate($response);
