<?php

declare(strict_types=1);

use App\Http\Middleware\AdminThemeMiddleware;
use App\Modules\Auth\Http\Middleware\RequireAdminAuthentication;
use App\Modules\Auth\Http\Middleware\RequireAdminRole;
use App\Modules\Auth\Http\Middleware\RequirePermission;
use App\Modules\DatabaseBackup\Http\Controllers\DatabaseBackupController;
use Marwa\Framework\Facades\Router;

Router::group(['prefix' => 'admin', 'middleware' => [AdminThemeMiddleware::class, RequireAdminAuthentication::class, RequireAdminRole::class]], static function ($routes): void {
    $routes->get('/database-backups', [DatabaseBackupController::class, 'index'])
        ->middleware(new RequirePermission('database_backup.view'))
        ->name('admin.database-backups.index')
        ->register();

    $routes->post('/database-backups/settings', [DatabaseBackupController::class, 'updateSettings'])
        ->middleware(new RequirePermission('database_backup.manage'))
        ->name('admin.database-backups.settings')
        ->register();

    $routes->post('/database-backups/backup', [DatabaseBackupController::class, 'backupNow'])
        ->middleware(new RequirePermission('database_backup.manage'))
        ->name('admin.database-backups.backup')
        ->register();

    $routes->post('/database-backups/restore', [DatabaseBackupController::class, 'restore'])
        ->middleware(new RequirePermission('database_backup.restore'))
        ->name('admin.database-backups.restore')
        ->register();
});
