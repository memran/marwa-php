<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(new RequirePermission('dashboard.view'))
        ->name('admin.dashboard.index')
        ->register();
    $routes->post('/dashboard/save', [DashboardController::class, 'saveWidgets'])->register();
    $routes->post('/dashboard/reset', [DashboardController::class, 'reset'])->register();
    $routes->get('/dashboard/widget/{id}/refresh', [DashboardController::class, 'refreshWidget'])->register();
});
