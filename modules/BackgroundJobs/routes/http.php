<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\BackgroundJobs\Http\Controllers\BackgroundJobsController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class]], static function ($routes): void {
    $routes->get('/background-jobs', [BackgroundJobsController::class, 'index'])
        ->middleware(new RequirePermission('background_jobs.view'))
        ->name('admin.background-jobs.index')
        ->register();

    $routes->get('/background-jobs/{id}', [BackgroundJobsController::class, 'show'])
        ->middleware(new RequirePermission('background_jobs.view'))
        ->name('admin.background-jobs.show')
        ->register();

    $routes->post('/background-jobs/{id}/run', [BackgroundJobsController::class, 'runNow'])
        ->middleware(new RequirePermission('background_jobs.run'))
        ->name('admin.background-jobs.run')
        ->register();
});
