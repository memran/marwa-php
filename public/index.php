<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;

$app = new Application(dirname(__DIR__));

$app->boot();

$http = $app->make(HttpRequestFactory::class);
$request = $http->request();

$kernel = $app->make(HttpKernel::class);
$response = $kernel->handle($request);
$kernel->terminate($response);
