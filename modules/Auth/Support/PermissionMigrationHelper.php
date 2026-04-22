<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use Marwa\DB\Facades\DB;

final class PermissionMigrationHelper
{
    /**
     * @param array<int, array{name: string, slug: string, group: string, description?: string}> $permissions
     * @param list<string> $assignToRoles List of role slugs to assign these permissions to
     */
    public static function insertPermissions(array $permissions, array $assignToRoles = ['admin']): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('slug', $assignToRoles)
            ->pluck('id')
            ->toArray();

        foreach ($permissions as $perm) {
            $perm['created_at'] = date('Y-m-d H:i:s');
            $perm['updated_at'] = date('Y-m-d H:i:s');
            
            // Check if permission already exists
            $existing = DB::table('permissions')->where('slug', '=', $perm['slug'])->first();
            if ($existing) {
                $permissionId = (int) ($existing instanceof \stdClass ? $existing->id : $existing['id']);
            } else {
                DB::table('permissions')->insert($perm);
                $permissionId = (int) DB::connection()->getPdo()->lastInsertId();
            }

            foreach ($roleIds as $roleId) {
                $exists = DB::table('role_permission')
                    ->where('role_id', '=', $roleId)
                    ->where('permission_id', '=', $permissionId)
                    ->count() > 0;

                if (!$exists) {
                    DB::table('role_permission')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    /**
     * @param list<string> $slugs
     */
    public static function removePermissions(array $slugs): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->toArray();

        if ($permissionIds !== []) {
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }
}
