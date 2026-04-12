<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Activity\Http\Controllers\ActivityController;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/activity', [ActivityController::class, 'index'])->name('admin.activity.index')->register();
});
