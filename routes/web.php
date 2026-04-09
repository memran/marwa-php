<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\AdminThemeMiddleware;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class]], static function ($routes): void {
    $routes->get('/', [DashboardController::class, 'index'])->name('admin.dashboard')->register();
});
