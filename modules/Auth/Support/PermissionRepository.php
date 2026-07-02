<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Marwa\DB\ORM\QueryBuilder;

final class PermissionRepository
{
    /**
     * @return list<Permission>
     */
    public function all(): array
    {
        return $this->query()->get();
    }

    public function findById(int $id): ?Permission
    {
        return Permission::find($id);
    }

    public function findBySlug(string $slug): ?Permission
    {
        return Permission::findBy('slug', $slug);
    }

    public function count(): int
    {
        return (int) Permission::query()->count();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
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
        $builder = Permission::where('slug', '=', $slug);

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

    /**
     * @return list<Permission>
     */
    public function byGroup(string $group): array
    {
        return $this->query($group)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * @return list<Permission>
     */
    public function getByRoleId(int $roleId): array
    {
        $role = Role::find($roleId);
        if ($role === null) {
            return [];
        }

        return $role->permissions();
    }

    /**
     * @return array<string, list<Permission>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->query()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get() as $permission) {
            $groupName = (string) ($permission->getAttribute('group') ?? 'other');
            $groups[$groupName][] = $permission;
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public function groupNames(): array
    {
        return array_keys($this->grouped());
    }

    private function query(string $group = ''): QueryBuilder
    {
        $builder = Permission::query();

        $group = trim($group);
        if ($group !== '') {
            $builder->where('group', '=', $group);
        }

        return $builder;
    }
}
