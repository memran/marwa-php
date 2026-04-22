<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'permissions'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
        ]);
    }
};
