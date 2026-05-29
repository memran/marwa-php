<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Permission;
use App\Support\AdminSearch;
use Marwa\DB\Facades\DB;
use Marwa\DB\ORM\QueryBuilder;

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
        $permissionIds = DB::table('role_permission')
            ->where('role_id', '=', $roleId)
            ->pluck('permission_id')
            ->toArray();

        if ($permissionIds === []) {
            return [];
        }

        return Permission::whereIn('id', array_map('intval', $permissionIds))
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();
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
        $groups = [];

        foreach ($this->query($group, $query)
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get() as $permission) {
            $groupName = (string) ($permission->getAttribute('group') ?? 'other');
            $groups[$groupName][] = $permission;
        }

        return $groups;
    }

    /**
     * @return array{data:list<Permission>,total:int,per_page:int,current_page:int,last_page:int,groups:array<string,list<Permission>>}
     */
    public function paginatedGroupedFiltered(
        string $query = '',
        string $group = '',
        int $page = 1,
        ?int $perPage = null,
        string $sort = 'group',
        string $direction = 'asc'
    ): array {
        $page = max(1, $page);
        $perPage = max(1, (int) ($perPage ?? config('settings.lifecycle.pagination.default_per_page', config('pagination.default_per_page', 12))));
        $sort = trim($sort);
        $direction = strtolower(trim($direction)) === 'desc' ? 'desc' : 'asc';

        $builder = Permission::newQuery()->getBaseBuilder();

        $group = trim($group);
        if ($group !== '') {
            $builder->where('group', '=', $group);
        }

        $query = trim($query);
        if ($query !== '') {
            $this->search->applyLikeFilters($builder, $query, ['name', 'slug', 'description', 'group']);
        }

        $this->applySort($builder, $sort, $direction);
        $pageData = $builder->paginate($perPage, $page);
        $pageData['data'] = array_map(
            static fn (array|object $row): Permission => Permission::newInstance(is_array($row) ? $row : (array) $row, true),
            $pageData['data']
        );

        $groups = [];
        foreach ($pageData['data'] as $permission) {
            $groupName = (string) ($permission->getAttribute('group') ?? 'other');
            $groups[$groupName][] = $permission;
        }

        $pageData['groups'] = $groups;

        return $pageData;
    }

    /**
     * @return list<string>
     */
    public function groupNames(): array
    {
        return array_keys($this->grouped());
    }

    private function query(string $group = '', string $query = ''): QueryBuilder
    {
        $builder = Permission::query();

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

    private function applySort(object $builder, string $sort, string $direction): void
    {
        $column = match ($sort) {
            'name' => 'name',
            'slug' => 'slug',
            'description' => 'description',
            'group' => 'group',
            default => 'group',
        };

        $builder->orderBy($column, $direction);
    }
}
