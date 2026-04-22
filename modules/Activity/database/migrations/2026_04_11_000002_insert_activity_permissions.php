<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Activity', 'slug' => 'activity.view', 'group' => 'activity'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'activity.view',
        ]);
    }
};
