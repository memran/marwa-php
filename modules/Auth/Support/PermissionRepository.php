<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use Marwa\DB\Facades\DB;

final class PermissionRepository
{
    public function all(): array
    {
        $rows = Permission::newQuery()->getBaseBuilder()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array|object $row): Permission => Permission::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    public function findById(int $id): ?Permission
    {
        $row = Permission::newQuery()->getBaseBuilder()
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : Permission::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function findBySlug(string $slug): ?Permission
    {
        $row = Permission::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug)
            ->first();

        return $row === null ? null : Permission::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $permission = $this->findById($id);
        if ($permission === null) {
            return false;
        }

        $permission->fill($data);

        return $permission->save();
    }

    public function hasSlug(string $slug, ?int $ignoreId = null): bool
    {
        $builder = Permission::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug);

        if ($ignoreId !== null) {
            $builder->where('id', '!=', $ignoreId);
        }

        return $builder->count() > 0;
    }

    public function delete(int $id): bool
    {
        $permission = $this->findById($id);
        if ($permission === null) {
            return false;
        }

        return $permission->delete();
    }

    public function byGroup(string $group): array
    {
        $rows = Permission::newQuery()->getBaseBuilder()
            ->where('group', '=', $group)
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array|object $row): Permission => Permission::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    public function getByRoleId(int $roleId): array
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

    public function grouped(): array
    {
        $all = $this->all();
        $groups = [];

        foreach ($all as $permission) {
            $group = $permission->getAttribute('group') ?? 'other';
            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }
            $groups[$group][] = $permission;
        }

        return $groups;
    }

    public function getAllSlugs(int $roleId): array
    {
        $permissions = $this->getByRoleId($roleId);
        return array_map(
            static fn (Permission $p): string => $p->getAttribute('slug'),
            $permissions
        );
    }
}
