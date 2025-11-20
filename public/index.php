<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Marwa\Framework\Adapters\HttpRequestFactory;
use Marwa\Framework\Application;
use Marwa\Framework\HttpKernel;

// Bootstrap
$app = new Application(dirname(__DIR__));
$app->boot();

// Request + Kernel
/** @var HttpFactoryInterface $http */
$http = $app->make(HttpRequestFactory::class);
$request = $http->request();

// /** @var HttpKernel $kernel */
$kernel = new HttpKernel($app);
$response = $kernel->handle($request);
$kernel->terminate($response);
