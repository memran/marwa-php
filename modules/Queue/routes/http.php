<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Queue\Http\Controllers\QueueController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/queue', [QueueController::class, 'index'])
        ->middleware(new RequirePermission('queue.view'))
        ->name('admin.queue.index')
        ->register();

    $routes->get('/queue/{id}', [QueueController::class, 'show'])
        ->middleware(new RequirePermission('queue.view'))
        ->name('admin.queue.show')
        ->register();

    $routes->post('/queue/{id}/retry', [QueueController::class, 'retry'])
        ->middleware(new RequirePermission('queue.retry'))
        ->name('admin.queue.retry')
        ->register();
});
