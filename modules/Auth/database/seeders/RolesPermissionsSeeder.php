<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Auth\Support\DefaultIspRoleCatalog;
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

            $this->createIspRoles($roleRepo, $permRepo);
            $this->assignUserRolePermissions($roleRepo, $permRepo);
        }

        private function createIspRoles(RoleRepository $roleRepo, PermissionRepository $permRepo): void
        {
            foreach (DefaultIspRoleCatalog::all() as $definition) {
                if ($roleRepo->findBySlug($definition['slug']) !== null) {
                    continue;
                }

                $role = $roleRepo->create([
                    'name' => $definition['name'],
                    'slug' => $definition['slug'],
                    'level' => $definition['level'],
                    'description' => $definition['description'],
                ]);

                $roleRepo->syncPermissions(
                    (int) $role->getKey(),
                    $this->permissionIds($definition['permissions'], $permRepo),
                );
            }
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

            $permIds = $this->permissionIds($permSlugs, $permRepo);

            if ($permIds !== []) {
                $roleRepo->syncPermissions((int) $role->getKey(), $permIds);
            }
        }

        /**
         * @param list<string> $permissionSlugs
         * @return list<int>
         */
        private function permissionIds(array $permissionSlugs, PermissionRepository $permRepo): array
        {
            $permissionIds = [];

            foreach ($permissionSlugs as $slug) {
                $permission = $permRepo->findBySlug($slug);
                if ($permission !== null) {
                    $permissionIds[] = (int) $permission->getKey();
                }
            }

            return $permissionIds;
        }
    }
}
