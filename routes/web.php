<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/', [DashboardController::class, 'index'])
        ->middleware(new RequirePermission('dashboard.view'))
        ->name('admin.dashboard')
        ->register();
    $routes->get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(new RequirePermission('dashboard.view'))
        ->register();
});
