<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\SecurityRiskReportController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/', static fn(): \Psr\Http\Message\ResponseInterface => \Marwa\Router\Response::redirect('/admin/dashboard', 302))
        ->register();
    $routes->get('/security/risk', [SecurityRiskReportController::class, 'index'])
        ->name('admin.security.risk')
        ->register();
});
