<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\DatabaseManager\Http\Controllers\DatabaseManagerController;
use Marwa\Framework\Facades\Router;

$databaseManagerEnabled = (bool) env(
    'DATABASE_MANAGER_ENABLED',
    !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
);

if ($databaseManagerEnabled) {
    Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
        $routes->get('/database', [DatabaseManagerController::class, 'index'])
            ->middleware(new RequirePermission('database.view'))
            ->name('admin.database.index')
            ->register();
        $routes->post('/database', [DatabaseManagerController::class, 'execute'])
            ->middleware(new RequirePermission('database.query'))
            ->name('admin.database.execute')
            ->register();
    });
}
