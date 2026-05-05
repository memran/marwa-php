<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Database Backups', 'slug' => 'database_backup.view', 'group' => 'database_backup'],
            ['name' => 'Manage Database Backups', 'slug' => 'database_backup.manage', 'group' => 'database_backup'],
            ['name' => 'Restore Database Backups', 'slug' => 'database_backup.restore', 'group' => 'database_backup'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'database_backup.view',
            'database_backup.manage',
            'database_backup.restore',
        ]);
    }
};
