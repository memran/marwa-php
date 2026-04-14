<?php

declare(strict_types=1);

namespace App\Modules\Auth\Database\Seeders;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\RoleRepository;
use App\Modules\Auth\Support\PermissionRepository;
use Marwa\DB\Seeder\Seeder;

final class RolesPermissionsSeeder implements Seeder
{
    public function run(): void
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return;
        }

        $roleRepo = app(RoleRepository::class);
        $permRepo = app(PermissionRepository::class);

        if (count($roleRepo->all()) > 0) {
            return;
        }

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'level' => 5, 'description' => 'Full system access', 'is_system' => 1],
            ['name' => 'Admin', 'slug' => 'admin', 'level' => 5, 'description' => 'Administrative access', 'is_system' => 1],
            ['name' => 'Manager', 'slug' => 'manager', 'level' => 4, 'description' => 'Team management', 'is_system' => 1],
            ['name' => 'Staff', 'slug' => 'staff', 'level' => 2, 'description' => 'Operational access', 'is_system' => 1],
            ['name' => 'Viewer', 'slug' => 'viewer', 'level' => 1, 'description' => 'Read-only access', 'is_system' => 1],
        ];

        $permissions = [
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard'],
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users'],
            ['name' => 'Restore Users', 'slug' => 'users.restore', 'group' => 'users'],
            ['name' => 'View Activity', 'slug' => 'activity.view', 'group' => 'activity'],
            ['name' => 'View Notifications', 'slug' => 'notifications.view', 'group' => 'notifications'],
            ['name' => 'Manage Notifications', 'slug' => 'notifications.manage', 'group' => 'notifications'],
            ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name' => 'View Database', 'slug' => 'database.view', 'group' => 'database'],
            ['name' => 'Query Database', 'slug' => 'database.query', 'group' => 'database'],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'group' => 'roles'],
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions'],
            ['name' => 'Manage Permissions', 'slug' => 'permissions.manage', 'group' => 'permissions'],
        ];

        $createdRoles = [];
        foreach ($roles as $roleData) {
            $role = $roleRepo->create($roleData);
            $createdRoles[$roleData['slug']] = $role->getKey();
        }

        $createdPerms = [];
        foreach ($permissions as $permData) {
            $perm = $permRepo->create($permData);
            $createdPerms[$permData['slug']] = $perm->getKey();
        }

        $rolePermAssignments = [
            'super_admin' => array_values($createdPerms),
            'admin' => array_values($createdPerms),
            'manager' => [
                $createdPerms['dashboard.view'],
                $createdPerms['users.view'],
                $createdPerms['activity.view'],
                $createdPerms['notifications.view'],
                $createdPerms['settings.view'],
            ],
            'staff' => [
                $createdPerms['dashboard.view'],
                $createdPerms['notifications.view'],
            ],
            'viewer' => [
                $createdPerms['dashboard.view'],
            ],
        ];

        foreach ($rolePermAssignments as $roleSlug => $permIds) {
            if (isset($createdRoles[$roleSlug])) {
                $roleRepo->syncPermissions($createdRoles[$roleSlug], $permIds);
            }
        }
    }
}