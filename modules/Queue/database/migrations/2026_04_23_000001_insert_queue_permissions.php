<?php

declare(strict_types=1);

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\CLI\AbstractMigration;

return new class extends AbstractMigration {
    public function up(): void
    {
        PermissionMigrationHelper::insertPermissions([
            ['name' => 'View Queue Jobs', 'slug' => 'queue.view', 'group' => 'queue'],
            ['name' => 'Retry Queue Jobs', 'slug' => 'queue.retry', 'group' => 'queue'],
            ['name' => 'Run Queue Worker', 'slug' => 'queue.work', 'group' => 'queue'],
        ]);
    }

    public function down(): void
    {
        PermissionMigrationHelper::removePermissions([
            'queue.view',
            'queue.retry',
            'queue.work',
        ]);
    }
};
