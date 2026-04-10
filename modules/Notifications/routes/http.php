<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/notifications', fn () => Response::json([
    'module' => 'Notifications Module',
    'ok' => true,
]))->register();
