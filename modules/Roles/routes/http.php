<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Roles\Http\Controllers\RolesController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/roles', [RolesController::class, 'index'])->name('admin.roles.index')->register();
    $routes->get('/roles/{id}/edit', [RolesController::class, 'edit'])->name('admin.roles.edit')->register();
    $routes->put('/roles/{id}', [RolesController::class, 'update'])->name('admin.roles.update')->register();
    $routes->delete('/roles/{id}', [RolesController::class, 'destroy'])->name('admin.roles.destroy')->register();
    $routes->get('/permissions', [RolesController::class, 'permissions'])->name('admin.permissions.index')->register();
});