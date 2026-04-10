<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Controllers\AuthController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class]], static function ($routes): void {
    $routes->get('/login', [AuthController::class, 'login'])->name('admin.login')->register();
    $routes->post('/login', [AuthController::class, 'authenticate'])->name('admin.login.submit')->register();
    $routes->get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('admin.forgot-password')->register();
    $routes->post('/forgot-password', [AuthController::class, 'sendForgotPasswordLink'])->name('admin.forgot-password.submit')->register();
    $routes->get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('admin.reset-password')->register();
    $routes->post('/reset-password/{token}', [AuthController::class, 'updatePassword'])->name('admin.reset-password.submit')->register();
    $routes->get('/logout', [AuthController::class, 'logout'])->name('admin.logout')->register();
});
