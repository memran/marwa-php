<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\Users\Http\Controllers\ProfileController;
use App\Modules\Users\Http\Controllers\UserCreateController;
use App\Modules\Users\Http\Controllers\UserDeleteController;
use App\Modules\Users\Http\Controllers\UserEditController;
use App\Modules\Users\Http\Controllers\UserExportController;
use App\Modules\Users\Http\Controllers\UserBulkActionController;
use App\Modules\Users\Http\Controllers\UserIndexController;
use App\Modules\Users\Http\Controllers\UserShowController;
use App\Modules\Users\Http\Controllers\UserStoreController;
use App\Modules\Users\Http\Controllers\UserUpdateController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/profile', [ProfileController::class, 'show'])
        ->name('admin.users.profile')
        ->register();

    $routes->post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('admin.users.profile.password')
        ->register();
});

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/users', [UserIndexController::class, 'index'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.index')
        ->register();

    $routes->get('/users/create', [UserCreateController::class, 'create'])
        ->middleware(new RequirePermission('users.create'))
        ->name('admin.users.create')
        ->register();

    $routes->get('/users/export/csv', [UserExportController::class, 'csv'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.export.csv')
        ->register();

    $routes->get('/users/export/pdf', [UserExportController::class, 'pdf'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.export.pdf')
        ->register();

    $routes->post('/users', [UserStoreController::class, 'store'])
        ->middleware(new RequirePermission('users.create'))
        ->name('admin.users.store')
        ->register();

    $routes->post('/users/bulk-delete', [UserBulkActionController::class, 'delete'])
        ->middleware(new RequirePermission('users.delete'))
        ->name('admin.users.bulk.destroy')
        ->register();

    $routes->post('/users/bulk-status', [UserBulkActionController::class, 'status'])
        ->middleware(new RequirePermission('users.edit'))
        ->name('admin.users.bulk.status')
        ->register();

    $routes->get('/users/{id}', [UserShowController::class, 'show'])
        ->middleware(new RequirePermission('users.view'))
        ->name('admin.users.show')
        ->register();

    $routes->get('/users/{id}/edit', [UserEditController::class, 'edit'])
        ->middleware(new RequirePermission('users.edit'))
        ->name('admin.users.edit')
        ->register();

    $routes->post('/users/{id}', [UserUpdateController::class, 'update'])
        ->middleware(new RequirePermission('users.edit'))
        ->name('admin.users.update')
        ->register();

    $routes->post('/users/{id}/delete', [UserDeleteController::class, 'destroy'])
        ->middleware(new RequirePermission('users.delete'))
        ->name('admin.users.destroy')
        ->register();
});
