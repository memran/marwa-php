<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Auth\Support\PermissionRepository;
use Marwa\DB\Seeder\Seeder;

if (!class_exists(RolesPermissionsSeeder::class, false)) {
    final class RolesPermissionsSeeder implements Seeder
    {
        public function run(): void
        {
            $roleRepo = app(RoleRepository::class);
            $permRepo = app(PermissionRepository::class);

            // Roles are created via migration: 2026_04_01_000004_insert_default_roles.php
            // Permissions are created via respective module migrations.

            $this->assignUserRolePermissions($roleRepo, $permRepo);
        }

        private function assignUserRolePermissions(RoleRepository $roleRepo, PermissionRepository $permRepo): void
        {
            $role = $roleRepo->findBySlug('user');
            if (!$role) {
                return;
            }

            $permSlugs = [
                'dashboard.view',
                'notifications.view',
            ];

            $permIds = [];
            foreach ($permSlugs as $slug) {
                $perm = $permRepo->findBySlug($slug);
                if ($perm) {
                    $permIds[] = (int) $perm->getKey();
                }
            }

            if ($permIds !== []) {
                $roleRepo->syncPermissions((int) $role->getKey(), $permIds);
            }
        }
    }
}
