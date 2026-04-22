<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Notifications', 'slug' => 'notifications.view', 'group' => 'notifications'],
            ['name' => 'Manage Notifications', 'slug' => 'notifications.manage', 'group' => 'notifications'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'notifications.view',
            'notifications.manage',
        ]);
    }
};
