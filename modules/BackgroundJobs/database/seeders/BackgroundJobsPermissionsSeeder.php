<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Database\Seeders;

use App\Modules\Auth\Support\PermissionMigrationHelper;
use Marwa\DB\Seeder\Seeder;

if (!class_exists(\App\Modules\BackgroundJobs\Database\Seeders\BackgroundJobsPermissionsSeeder::class, false)) {
    final class BackgroundJobsPermissionsSeeder implements Seeder
    {
        public function run(): void
        {
            PermissionMigrationHelper::insertPermissions([
                ['name' => 'View Background Jobs', 'slug' => 'background_jobs.view', 'group' => 'background_jobs'],
            ]);
        }
    }
}
