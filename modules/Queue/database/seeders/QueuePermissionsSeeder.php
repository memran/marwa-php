<?php

declare(strict_types=1);

namespace App\Modules\Queue\Database\Seeders;

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\Seeder\Seeder;

if (!class_exists(\App\Modules\Queue\Database\Seeders\QueuePermissionsSeeder::class, false)) {
    final class QueuePermissionsSeeder implements Seeder
    {
        public function run(): void
        {
            PermissionMigrationHelper::insertPermissions([
                ['name' => 'View Queue Jobs', 'slug' => 'queue.view', 'group' => 'queue'],
                ['name' => 'Retry Queue Jobs', 'slug' => 'queue.retry', 'group' => 'queue'],
                ['name' => 'Run Queue Worker', 'slug' => 'queue.work', 'group' => 'queue'],
            ]);
        }
    }
}
