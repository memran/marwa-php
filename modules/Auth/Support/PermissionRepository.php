<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use App\Support\AdminSearch;
use Marwa\DB\Facades\DB;
use Marwa\DB\Query\Builder;

final class PermissionRepository
{
    public function __construct(
        private readonly AdminSearch $search = new AdminSearch(),
    ) {}

    /**
     * @return list<Permission>
     */
    public function all(): array
    {
        return $this->hydrate($this->query()->get());
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

    /**
     * @return list<Permission>
     */
    public function byGroup(string $group): array
    {
        return $this->hydrate($this->query($group)->get());
    }

    /**
     * @return list<Permission>
     */
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

    /**
     * @return array<string, list<Permission>>
     */
    public function grouped(): array
    {
        return $this->groupedFiltered();
    }

    /**
     * @return array<string, list<Permission>>
     */
    public function groupedFiltered(string $query = '', string $group = ''): array
    {
        $all = $this->hydrate($this->query($group, $query)->get());
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

    /**
     * @return list<string>
     */
    public function groupNames(): array
    {
        return array_keys($this->grouped());
    }

    /**
     * @return list<string>
     */
    public function getAllSlugs(int $roleId): array
    {
        $permissions = $this->getByRoleId($roleId);
        return array_map(
            static fn (Permission $p): string => $p->getAttribute('slug'),
            $permissions
        );
    }

    private function query(string $group = '', string $query = ''): Builder
    {
        $builder = Permission::newQuery()->getBaseBuilder()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc');

        $group = trim($group);
        if ($group !== '') {
            $builder->where('group', '=', $group);
        }

        $query = trim($query);
        if ($query !== '') {
            $this->search->applyLikeFilters($builder, $query, ['name', 'slug', 'description', 'group']);
        }

        return $builder;
    }

    /**
     * @param array<int, array<string, mixed>|object> $rows
     * @return list<Permission>
     */
    private function hydrate(array $rows): array
    {
        return array_map(
            static fn (array|object $row): Permission => Permission::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }
}
