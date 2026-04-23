<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\ApiToken\Http\Controllers\ApiTokenController;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use Marwa\Framework\Facades\Router;

Router::group([
    'prefix' => 'admin',
    'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class],
], static function ($routes): void {
    $routes->get('/api-tokens', [ApiTokenController::class, 'index'])
        ->middleware(new RequirePermission('api_token.view'))
        ->name('admin.api-tokens.index')
        ->register();

    $routes->get('/api-tokens/create', [ApiTokenController::class, 'create'])
        ->middleware(new RequirePermission('api_token.create'))
        ->name('admin.api-tokens.create')
        ->register();

    $routes->post('/api-tokens', [ApiTokenController::class, 'store'])
        ->middleware(new RequirePermission('api_token.create'))
        ->name('admin.api-tokens.store')
        ->register();

    $routes->get('/api-tokens/show/{id}', [ApiTokenController::class, 'show'])
        ->middleware(new RequirePermission('api_token.view'))
        ->where('id', '[0-9]+')
        ->name('admin.api-tokens.show')
        ->register();

    $routes->post('/api-tokens/show/{id}/toggle', [ApiTokenController::class, 'toggle'])
        ->middleware(new RequirePermission('api_token.create'))
        ->where('id', '[0-9]+')
        ->name('admin.api-tokens.toggle')
        ->register();

    $routes->post('/api-tokens/show/{id}/revoke', [ApiTokenController::class, 'revoke'])
        ->middleware(new RequirePermission('api_token.revoke'))
        ->where('id', '[0-9]+')
        ->name('admin.api-tokens.revoke')
        ->register();
});