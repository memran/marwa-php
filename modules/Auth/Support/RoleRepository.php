<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Users\Models\User;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Marwa\DB\Facades\DB;

final class RoleRepository
{
    /**
     * @return list<Role>
     */
    public function all(): array
    {
        $rows = Role::newQuery()->getBaseBuilder()
            ->orderBy('level', 'desc')
            ->get();

        return array_map(
            static fn (array|object $row): Role => Role::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    public function findById(int $id): ?Role
    {
        $row = Role::newQuery()->getBaseBuilder()
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : Role::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function findBySlug(string $slug): ?Role
    {
        $row = Role::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug)
            ->first();

        return $row === null ? null : Role::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Role
    {
        return Role::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $role = $this->findById($id);
        if ($role === null) {
            return false;
        }

        $role->fill($data);

        return $role->save();
    }

    public function delete(int $id): bool
    {
        $role = $this->findById($id);
        if ($role === null) {
            return false;
        }

        if ($role->getAttribute('is_system')) {
            return false;
        }

        return $role->delete();
    }

    public function countUsers(int $roleId): int
    {
        return (int) User::newQuery()->getBaseBuilder()
            ->where('role_id', '=', $roleId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function hasSlug(string $slug, ?int $ignoreId = null): bool
    {
        $builder = Role::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug);

        if ($ignoreId !== null) {
            $builder->where('id', '!=', $ignoreId);
        }

        return $builder->count() > 0;
    }

    /**
     * @return list<string>
     */
    public function systemSlugs(): array
    {
        $rows = Role::newQuery()->getBaseBuilder()
            ->where('is_system', '=', 1)
            ->orderBy('level', 'desc')
            ->orderBy('slug', 'asc')
            ->pluck('slug')
            ->toArray();

        return array_values(array_filter(
            array_map(static fn (mixed $slug): string => (string) $slug, $rows),
            static fn (string $slug): bool => $slug !== ''
        ));
    }

    /**
     * @return list<Permission>
     */
    public function getPermissions(int $roleId): array
    {
        $permissionIds = DB::table('role_permission')
            ->where('role_id', '=', $roleId)
            ->pluck('permission_id')
            ->toArray();

        if ($permissionIds === []) {
            return [];
        }

        $rows = Permission::newQuery()->getBaseBuilder()
            ->whereIn('id', array_map('intval', $permissionIds))
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array $row): Permission => Permission::newInstance($row, true),
            $rows
        );
    }

    /**
     * @param list<int|string> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        DB::table('role_permission')
            ->where('role_id', '=', $roleId)
            ->delete();

        if (empty($permissionIds)) {
            return true;
        }

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permission')->insert([
                'role_id' => $roleId,
                'permission_id' => (int) $permissionId,
            ]);
        }

        return true;
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    public function levelOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Viewer'],
            ['value' => 2, 'label' => 'Staff'],
            ['value' => 3, 'label' => 'Custom'],
            ['value' => 4, 'label' => 'Manager'],
            ['value' => 5, 'label' => 'Admin'],
        ];
    }

    public function findByUserRole(string $userRole): ?Role
    {
        $roleSlug = strtolower(trim($userRole));

        $role = $this->findBySlug($roleSlug);
        if ($role !== null) {
            return $role;
        }

        $fallbackRoles = [
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'user' => 'admin',
            'manager' => 'manager',
            'staff' => 'staff',
            'viewer' => 'viewer',
        ];

        if (isset($fallbackRoles[$roleSlug])) {
            return $this->findBySlug($fallbackRoles[$roleSlug]);
        }

        return $this->findBySlug('staff');
    }
}
