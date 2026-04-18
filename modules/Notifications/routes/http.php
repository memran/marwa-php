<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Notifications\Http\Controllers\NotificationsController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/notifications', [NotificationsController::class, 'index'])
        ->middleware(new RequirePermission('notifications.view'))
        ->name('admin.notifications.index')
        ->register();
    $routes->get('/notifications/latest', [NotificationsController::class, 'latest'])
        ->middleware(new RequirePermission('notifications.view'))
        ->name('admin.notifications.latest')
        ->register();
    $routes->post('/notifications/{id}/read', [NotificationsController::class, 'markRead'])
        ->middleware(new RequirePermission('notifications.view'))
        ->name('admin.notifications.mark-read')
        ->register();
    $routes->post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])
        ->middleware(new RequirePermission('notifications.view'))
        ->name('admin.notifications.mark-all-read')
        ->register();
});

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->post('/notifications/{id}/delete', [NotificationsController::class, 'destroy'])
        ->middleware(new RequirePermission('notifications.manage'))
        ->name('admin.notifications.destroy')
        ->register();
    $routes->post('/notifications', [NotificationsController::class, 'store'])
        ->middleware(new RequirePermission('notifications.manage'))
        ->name('admin.notifications.store')
        ->register();
});
