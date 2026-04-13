<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\DatabaseManager\Http\Controllers\DatabaseManagerController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/database', [DatabaseManagerController::class, 'index'])->name('admin.database.index')->register();
    $routes->post('/database', [DatabaseManagerController::class, 'execute'])->name('admin.database.execute')->register();
});
