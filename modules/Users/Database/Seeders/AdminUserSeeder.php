<?php

declare(strict_types=1);

namespace App\Modules\Users\Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;
use Marwa\DB\Seeder\Seeder;

if (!class_exists(\App\Modules\Users\Database\Seeders\AdminUserSeeder::class, false)) {
    final class AdminUserSeeder implements Seeder
    {
        public function run(): void
        {
            if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
                return;
            }

            if (User::query()->count() > 0) {
                return;
            }

            $email = trim((string) env('ADMIN_BOOTSTRAP_EMAIL', 'admin@marwa.test'));
            $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', 'ExampleAdminPassword123!');

            $adminRole = Role::newQuery()->getBaseBuilder()
                ->where('slug', '=', 'admin')
                ->first();

            if ($adminRole === null) {
                // Fallback if migrations didn't run for some reason during seed-only call
                $adminRole = Role::create([
                    'name' => 'Admin',
                    'slug' => 'admin',
                    'level' => 10,
                    'description' => 'Administrative access',
                    'is_system' => 1,
                ]);
            } else {
                $adminRole = Role::newInstance(is_array($adminRole) ? $adminRole : (array) $adminRole, true);
            }

            $roleId = (int) $adminRole->getKey();

            User::create([
                'name' => 'Administrator',
                'email' => $email !== '' ? $email : 'admin@marwa.test',
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $roleId,
                'is_active' => true,
            ]);
        }
    }
}
