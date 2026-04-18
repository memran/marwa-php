<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Settings\Http\Controllers\SettingsController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/settings', [SettingsController::class, 'index'])
        ->middleware(new RequirePermission('settings.view'))
        ->name('admin.settings.index')
        ->register();
    $routes->post('/settings', [SettingsController::class, 'update'])
        ->middleware(new RequirePermission('settings.manage'))
        ->name('admin.settings.update')
        ->register();
    $routes->post('/settings/purge-cache', [SettingsController::class, 'purgeCache'])
        ->middleware(new RequirePermission('settings.manage'))
        ->name('admin.settings.purge-cache')
        ->register();
    $routes->post('/settings/clear-logs', [SettingsController::class, 'clearLogs'])
        ->middleware(new RequirePermission('settings.manage'))
        ->name('admin.settings.clear-logs')
        ->register();
});
