<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class RoleRepository
{
    /**
     * @return list<Role>
     */
    public function all(): array
    {
        $roles = $this->filterRoles(Role::query()
            ->orderBy('level', 'desc')
            ->get());

        if ($roles !== []) {
            $roles[0]->permissionsRelation()->eagerLoad($roles, 'permissionsRelation');
        }

        return $roles;
    }

    public function findById(int $id): ?Role
    {
        $role = Role::find($id);

        return $role instanceof Role ? $role : null;
    }

    public function findBySlug(string $slug): ?Role
    {
        $role = Role::findBy('slug', $slug);

        return $role instanceof Role ? $role : null;
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
        return (int) User::where('role_id', '=', $roleId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function hasSlug(string $slug, ?int $ignoreId = null): bool
    {
        $builder = Role::where('slug', '=', $slug);

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
        $rows = $this->filterRoles(Role::where('is_system', '=', 1)
            ->orderBy('level', 'desc')
            ->orderBy('slug', 'asc')
            ->get());

        return array_values(array_filter(
            array_map(static fn (Role $role): string => (string) $role->getAttribute('slug'), $rows),
            static fn (string $slug): bool => $slug !== ''
        ));
    }

    /**
     * @return list<Permission>
     */
    public function getPermissions(int $roleId): array
    {
        $role = Role::find($roleId);

        if ($role === null) {
            return [];
        }

        return $role->permissions();
    }

    /**
     * @param list<int|string> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $role = $this->findById($roleId);
        if ($role === null) {
            return false;
        }

        $role->syncPermissionIds(array_map(
            static fn (int|string $permissionId): int => (int) $permissionId,
            $permissionIds
        ));

        return true;
    }

    /**
     * @param array<int, mixed> $rows
     * @return list<Role>
     */
    private function filterRoles(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            static fn (mixed $row): bool => $row instanceof Role
        ));
    }
}
