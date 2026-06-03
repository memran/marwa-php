<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Users\Http\Controllers\UsersController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/profile', [UsersController::class, 'profile'])
        ->name('admin.users.profile')
        ->register();

    $routes->get('/users', [UsersController::class, 'index'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.index')
        ->register();

    $routes->get('/users/create', [UsersController::class, 'create'])
        ->middleware(new RequirePermission('users.create'))
        ->name('admin.users.create')
        ->register();

    $routes->get('/users/export/csv', [UsersController::class, 'exportCsv'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.export.csv')
        ->register();

    $routes->get('/users/export/pdf', [UsersController::class, 'exportPdf'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.export.pdf')
        ->register();

    $routes->post('/users', [UsersController::class, 'store'])
        ->middleware(new RequirePermission('users.create'))
        ->name('admin.users.store')
        ->register();

    $routes->get('/users/{id}', [UsersController::class, 'show'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.show')
        ->register();

    $routes->get('/users/{id}/edit', [UsersController::class, 'edit'])
        ->middleware(new RequirePermission('users.edit'))
        ->name('admin.users.edit')
        ->register();

    $routes->post('/users/{id}', [UsersController::class, 'update'])
        ->middleware(new RequirePermission('users.edit'))
        ->name('admin.users.update')
        ->register();

    $routes->post('/users/{id}/delete', [UsersController::class, 'destroy'])
        ->middleware(new RequirePermission('users.delete'))
        ->name('admin.users.destroy')
        ->register();
});
