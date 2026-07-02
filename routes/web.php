<?php

declare(strict_types=1);

use App\Http\Controllers\AIController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'ai'], static function ($routes): void {
    $routes->get('/complete', [AIController::class, 'complete'])->name('ai.complete')->register();
    $routes->get('/chat', [AIController::class, 'chat'])->name('ai.chat')->register();
    $routes->get('/stream', [AIController::class, 'stream'])->name('ai.stream')->register();
    $routes->get('/embed', [AIController::class, 'embed'])->name('ai.embed')->register();
    $routes->get('/image', [AIController::class, 'image'])->name('ai.image')->register();
    $routes->get('/tools', [AIController::class, 'tools'])->name('ai.tools')->register();
    $routes->get('/providers', [AIController::class, 'providers'])->name('ai.providers')->register();
});

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/', static fn(): \Psr\Http\Message\ResponseInterface => \Marwa\Router\Response::redirect('/admin/dashboard', 302))
        ->register();
});
