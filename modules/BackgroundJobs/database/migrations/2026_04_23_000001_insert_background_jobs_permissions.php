<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Background Jobs', 'slug' => 'background_jobs.view', 'group' => 'background_jobs'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'background_jobs.view',
        ]);
    }
};
