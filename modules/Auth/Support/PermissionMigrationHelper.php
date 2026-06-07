<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Marwa\DB\Facades\DB;

final class PermissionMigrationHelper
{
    /**
     * @param array<int, array{name: string, slug: string, group: string, description?: string}> $permissions
     * @param list<string> $assignToRoles List of role slugs to assign these permissions to
     */
    public static function insertPermissions(array $permissions, array $assignToRoles = ['admin']): void
    {
        if (!self::tablesAreReady(['roles', 'permissions', 'role_permission'])) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('slug', $assignToRoles)
            ->pluck('id')
            ->toArray();
        $roles = array_values(array_filter(array_map(
            static fn (mixed $roleId): ?Role => Role::find((int) $roleId),
            $roleIds
        ), static fn (?Role $role): bool => $role instanceof Role));

        foreach ($permissions as $perm) {
            $perm['created_at'] = date('Y-m-d H:i:s');
            $perm['updated_at'] = date('Y-m-d H:i:s');

            $permission = Permission::findBy('slug', $perm['slug']) ?? Permission::create($perm);
            $permissionId = (int) $permission->getKey();

            foreach ($roles as $role) {
                $role->attachPermissionId($permissionId);
            }
        }
    }

    /**
     * @param list<string> $slugs
     */
    public static function removePermissions(array $slugs): void
    {
        if (!self::tablesAreReady(['permissions', 'role_permission'])) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    /**
     * @param list<string> $tables
     */
    private static function tablesAreReady(array $tables): bool
    {
        foreach ($tables as $table) {
            try {
                DB::table($table)->count();
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }
}
