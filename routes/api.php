<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/health', static fn (): \Psr\Http\Message\ResponseInterface => Response::json([
    'status' => 'ok',
    'app' => config('app.name', 'MarwaPHP'),
]))->name('health')->register();
