<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/settings', fn () => Response::json([
    'module' => 'Settings Module',
    'ok' => true,
]))->register();
