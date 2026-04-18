<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Roles\Http\Controllers\RolesController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/roles', [RolesController::class, 'index'])
        ->middleware(new RequirePermission('roles.view'))
        ->name('admin.roles.index')
        ->register();
    $routes->get('/roles/{id}/edit', [RolesController::class, 'edit'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.edit')
        ->register();
    $routes->put('/roles/{id}', [RolesController::class, 'update'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.update')
        ->register();
    $routes->delete('/roles/{id}', [RolesController::class, 'destroy'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.destroy')
        ->register();
    $routes->get('/permissions', [RolesController::class, 'permissions'])
        ->middleware(new RequirePermission('permissions.view'))
        ->name('admin.permissions.index')
        ->register();
});
