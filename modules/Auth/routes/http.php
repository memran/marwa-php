<?php

declare(strict_types=1);

use App\Modules\Auth\Http\Controllers\AdminController;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Middleware\AuthenticateMiddleware;
use App\Modules\Auth\Http\Middleware\GuestMiddleware;
use App\Modules\Auth\Http\Middleware\RoleMiddleware;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'auth', 'name' => 'auth.'], static function ($router): void {
    $router->get('/login', [AuthController::class, 'showLoginForm'])
        ->middleware(GuestMiddleware::class)
        ->name('login')
        ->register();

    $router->post('/login', [AuthController::class, 'login'])
        ->middleware(GuestMiddleware::class)
        ->register();

    $router->get('/register', [AuthController::class, 'showRegisterForm'])
        ->middleware(GuestMiddleware::class)
        ->name('register')
        ->register();

    $router->post('/register', [AuthController::class, 'register'])
        ->middleware(GuestMiddleware::class)
        ->register();

    $router->get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])
        ->middleware(GuestMiddleware::class)
        ->name('password.request')
        ->register();

    $router->post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])
        ->middleware(GuestMiddleware::class)
        ->name('password.email')
        ->register();

    $router->get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])
        ->middleware(GuestMiddleware::class)
        ->name('password.reset')
        ->register();

    $router->post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware(GuestMiddleware::class)
        ->name('password.update')
        ->register();
});

Router::group(['prefix' => 'admin', 'name' => 'admin.'], static function ($router): void {
    $router->get('/', [AdminController::class, 'dashboard'])
        ->middleware([AuthenticateMiddleware::class, RoleMiddleware::class])
        ->name('dashboard')
        ->register();

    $router->get('/users', [AdminController::class, 'users'])
        ->middleware([AuthenticateMiddleware::class, RoleMiddleware::class])
        ->name('users')
        ->register();

    $router->get('/roles', [AdminController::class, 'roles'])
        ->middleware([AuthenticateMiddleware::class, RoleMiddleware::class])
        ->name('roles')
        ->register();

    $router->get('/profile', [AuthController::class, 'profile'])
        ->middleware(AuthenticateMiddleware::class)
        ->name('profile')
        ->register();

    $router->get('/password/change', [AuthController::class, 'showChangePasswordForm'])
        ->middleware(AuthenticateMiddleware::class)
        ->name('password.change')
        ->register();

    $router->post('/password/change', [AuthController::class, 'changePassword'])
        ->middleware(AuthenticateMiddleware::class)
        ->name('password.change.update')
        ->register();

    $router->post('/logout', [AuthController::class, 'logout'])
        ->middleware(AuthenticateMiddleware::class)
        ->name('logout')
        ->register();

    $router->post('/theme', [AdminController::class, 'toggleTheme'])
        ->middleware(AuthenticateMiddleware::class)
        ->name('theme.toggle')
        ->register();
});
