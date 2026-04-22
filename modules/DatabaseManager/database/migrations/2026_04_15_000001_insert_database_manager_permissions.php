<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Database', 'slug' => 'database.view', 'group' => 'database'],
            ['name' => 'Query Database', 'slug' => 'database.query', 'group' => 'database'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'database.view',
            'database.query',
        ]);
    }
};
