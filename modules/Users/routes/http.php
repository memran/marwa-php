<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Users\Http\Controllers\CreateUserController;
use App\Modules\Users\Http\Controllers\DeleteUserController;
use App\Modules\Users\Http\Controllers\EditUserController;
use App\Modules\Users\Http\Controllers\ListUsersController;
use App\Modules\Users\Http\Controllers\RestoreUserController;
use App\Modules\Users\Http\Controllers\ShowUserProfileController;
use App\Modules\Users\Http\Controllers\StoreUserController;
use App\Modules\Users\Http\Controllers\UpdateUserController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/users', [ListUsersController::class, 'index'])->name('admin.users.index')->register();
    $routes->get('/users/create', [CreateUserController::class, 'create'])->name('admin.users.create')->register();
    $routes->get('/users/{id}', [ShowUserProfileController::class, 'show'])->name('admin.users.show')->register();
    $routes->post('/users', [StoreUserController::class, 'store'])->name('admin.users.store')->register();
    $routes->get('/users/{id}/edit', [EditUserController::class, 'edit'])->name('admin.users.edit')->register();
    $routes->post('/users/{id}', [UpdateUserController::class, 'update'])->name('admin.users.update')->register();
    $routes->post('/users/{id}/restore', [RestoreUserController::class, 'restore'])->name('admin.users.restore')->register();
    $routes->post('/users/{id}/delete', [DeleteUserController::class, 'destroy'])->name('admin.users.destroy')->register();
});
