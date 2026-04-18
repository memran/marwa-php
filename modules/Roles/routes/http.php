<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Roles\Http\Controllers\PermissionsController;
use App\Modules\Roles\Http\Controllers\RolesController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/roles', [RolesController::class, 'index'])
        ->middleware(new RequirePermission('roles.view'))
        ->name('admin.roles.index')
        ->register();
    $routes->get('/roles/create', [RolesController::class, 'create'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.create')
        ->register();
    $routes->post('/roles', [RolesController::class, 'store'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.store')
        ->register();
    $routes->get('/roles/{id}/edit', [RolesController::class, 'edit'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.edit')
        ->register();
    $routes->post('/roles/{id}', [RolesController::class, 'update'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.update')
        ->register();
    $routes->post('/roles/{id}/delete', [RolesController::class, 'destroy'])
        ->middleware(new RequirePermission('roles.manage'))
        ->name('admin.roles.destroy')
        ->register();
    $routes->get('/permissions', [PermissionsController::class, 'index'])
        ->middleware(new RequirePermission('permissions.view'))
        ->name('admin.permissions.index')
        ->register();
    $routes->get('/permissions/create', [PermissionsController::class, 'create'])
        ->middleware(new RequirePermission('permissions.manage'))
        ->name('admin.permissions.create')
        ->register();
    $routes->post('/permissions', [PermissionsController::class, 'store'])
        ->middleware(new RequirePermission('permissions.manage'))
        ->name('admin.permissions.store')
        ->register();
    $routes->get('/permissions/{id}/edit', [PermissionsController::class, 'edit'])
        ->middleware(new RequirePermission('permissions.manage'))
        ->name('admin.permissions.edit')
        ->register();
    $routes->post('/permissions/{id}', [PermissionsController::class, 'update'])
        ->middleware(new RequirePermission('permissions.manage'))
        ->name('admin.permissions.update')
        ->register();
    $routes->post('/permissions/{id}/delete', [PermissionsController::class, 'destroy'])
        ->middleware(new RequirePermission('permissions.manage'))
        ->name('admin.permissions.destroy')
        ->register();
});
