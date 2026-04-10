<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;
use Marwa\Router\Response;

Router::get('/activity', fn () => Response::json([
    'module' => 'Activity Module',
    'ok' => true,
]))->register();
