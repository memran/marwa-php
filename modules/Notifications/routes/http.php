<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Notifications\Http\Controllers\NotificationsController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/notifications', [NotificationsController::class, 'index'])->name('admin.notifications.index')->register();
    $routes->get('/notifications/latest', [NotificationsController::class, 'latest'])->name('admin.notifications.latest')->register();
    $routes->post('/notifications/{id}/read', [NotificationsController::class, 'markRead'])->name('admin.notifications.mark-read')->register();
    $routes->post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('admin.notifications.mark-all-read')->register();
    $routes->delete('/notifications/{id}', [NotificationsController::class, 'destroy'])->name('admin.notifications.destroy')->register();
    $routes->post('/notifications', [NotificationsController::class, 'store'])->name('admin.notifications.store')->register();
});
