<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'Restore Users', 'slug' => 'users.restore', 'group' => 'users'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.restore',
        ]);
    }
};
