<?php

declare(strict_types=1);

return [
    'name' => 'Database Backup Module',
    'slug' => 'database-backup',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\DatabaseBackup\DatabaseBackupServiceProvider::class,
    ],
    'requires' => [
        'auth',
        'settings',
    ],
    'paths' => [
        'views' => 'resources/views',
        'database/migrations' => 'database/migrations',
    ],
    'permissions' => [
        'database_backup.view' => 'View Database Backups',
        'database_backup.manage' => 'Manage Database Backups',
        'database_backup.restore' => 'Restore Database Backups',
    ],
    'menu' => [
        'section' => 'System',
        'label' => 'Database Backups',
        'route' => '/admin/database-backups',
        'icon' => 'database-zap',
        'permissions' => ['database_backup.view'],
    ],
    'routes' => [
        'http' => 'routes/http.php',
    ],
    'migrations' => [
        'database/migrations/2026_05_05_000001_insert_database_backup_permissions.php',
    ],
];
