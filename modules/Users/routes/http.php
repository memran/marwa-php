<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Users\Http\Controllers\UserController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/users', [UserController::class, 'index'])->name('admin.users.index')->register();
    $routes->get('/users/create', [UserController::class, 'create'])->name('admin.users.create')->register();
    $routes->post('/users', [UserController::class, 'store'])->name('admin.users.store')->register();
    $routes->get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit')->register();
    $routes->post('/users/{id}', [UserController::class, 'update'])->name('admin.users.update')->register();
    $routes->post('/users/{id}/delete', [UserController::class, 'destroy'])->name('admin.users.destroy')->register();
});
