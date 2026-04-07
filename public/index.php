<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;

$app = new Application(dirname(__DIR__));

$isDebugEnvironment = in_array(env('APP_ENV', 'production'), ['local', 'development'], true);
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
