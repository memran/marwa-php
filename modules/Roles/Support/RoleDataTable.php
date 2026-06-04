<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\RoleRepository;
use App\Support\DataTable\DataTableConfigInterface;
use App\Support\DataTable\DataTableOptionsInterface;
use App\Support\DataTable\DataTableRowActions;
use App\Support\Export\Column;

final class RoleDataTable implements DataTableConfigInterface, DataTableOptionsInterface
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly DataTableRowActions $actions,
    ) {
    }

    public function pageTitle(): string
    {
        return 'Roles';
    }

    public function pageDescription(): string
    {
        return 'Manage user roles and their permissions from one place.';
    }

    public function searchPlaceholder(): string
    {
        return 'Search roles...';
    }

    /**
     * @return array<string, string>
     */
    public function columnOptions(): array
    {
        return [
            'name' => 'Role',
            'slug' => 'Slug',
            'level' => 'Level',
            'permissions' => 'Permissions',
            'kind' => 'Type',
            'users' => 'Users',
        ];
    }

    /**
     * @return list<string>
     */
    public function sortableKeys(): array
    {
        return ['name', 'slug', 'level', 'users'];
    }

    public function basePath(): string
    {
        return '/admin/roles';
    }

    public function defaultSort(): string
    {
        return 'level';
    }

    public function defaultDirection(): string
    {
        return 'desc';
    }

    public function defaultFilter(): string
    {
        return 'all';
    }

    public function rowKey(mixed $row): string
    {
        return $row instanceof Role ? (string) $row->getKey() : '';
    }

    public function rowIsTrashed(mixed $row): bool
    {
        return false;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array{label:string,href:string,active:bool}>
     */
    public function filterItems(array $state, array $visibleColumns, callable $buildUrl): array
    {
        return array_map(
            fn (array $item): array => [
                'label' => $item['label'],
                'href' => $buildUrl(array_replace($state, ['filter' => $item['value'], 'page' => 1]), $visibleColumns),
                'active' => $state['filter'] === $item['value'],
            ],
            $this->statusOptions()
        );
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function statusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All roles'],
            ['value' => 'system', 'label' => 'System roles'],
            ['value' => 'custom', 'label' => 'Custom roles'],
        ];
    }

    /**
     * @param array<string, string|int|list<string>|null> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
     */
    public function hiddenFields(array $params, array $visibleColumns): array
    {
        $fields = [];
        foreach ($params as $name => $value) {
            if ($value !== null && $value !== '') {
                $fields[] = ['name' => (string) $name, 'value' => is_array($value) ? implode(',', $value) : (string) $value];
            }
        }

        foreach ($visibleColumns as $column) {
            $fields[] = ['name' => 'columns[]', 'value' => $column];
        }

        return $fields;
    }

    /**
     * @return array{bulk:array<string, mixed>, cells:array<string, array<string, mixed>>, actions:list<array<string, mixed>>}
     */
    public function buildRow(mixed $row): array
    {
        if (!$row instanceof Role) {
            return ['bulk' => [], 'cells' => [], 'actions' => []];
        }

        $isSystem = (bool) $row->getAttribute('is_system');
        $hasUsers = $this->roles->countUsers((int) $row->getKey()) > 0;

        return [
            'bulk' => $this->rowBulkMeta($row, $isSystem, $hasUsers),
            'cells' => $this->buildCells($row),
            'actions' => $this->buildRowActions($row, $isSystem),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildRowActions(mixed $row, bool $isTrashed = false, bool $isProtected = false): array
    {
        if (!$row instanceof Role) {
            return [];
        }

        $id = (string) $row->getKey();
        $isSystem = (bool) $row->getAttribute('is_system');
        $actions = [
            $this->actions->link('Edit', '/admin/roles/' . $id . '/edit', 'secondary', 'roles.manage'),
        ];

        if (!$isSystem) {
            $actions[] = $this->actions->formButton(
                'Delete',
                '/admin/roles/' . $id . '/delete',
                'danger',
                'trash-2',
                'roles.manage',
                'Delete this role?'
            );
        }

        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    public function rowBulkMeta(mixed $row, bool $isProtected = false, bool $isTrashed = false, bool $isActiveSession = false): array
    {
        if (!$row instanceof Role) {
            return [];
        }

        $isSystem = (bool) $row->getAttribute('is_system');
        $hasUsers = $this->roles->countUsers((int) $row->getKey()) > 0;

        return [
            'id' => (string) $row->getKey(),
            'disabled' => $isSystem || $hasUsers,
            'title' => (string) $row->getAttribute('name'),
            'label' => 'Select ' . (string) $row->getAttribute('name'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCells(mixed $row, bool $isProtected = false): array
    {
        if (!$row instanceof Role) {
            return [];
        }

        $isSystem = (bool) $row->getAttribute('is_system');
        $permissionCount = count($row->permissions());
        $userCount = $this->roles->countUsers((int) $row->getKey());

        return [
            'name' => [
                'type' => 'avatar_link',
                'value' => (string) $row->getAttribute('name'),
                'meta' => 'ID ' . (string) $row->getKey(),
                'avatar' => (string) $row->getAttribute('name'),
                'href' => '/admin/roles/' . (string) $row->getKey() . '/edit',
            ],
            'slug' => ['type' => 'text', 'value' => (string) $row->getAttribute('slug'), 'muted' => true],
            'level' => [
                'type' => 'badge',
                'value' => (string) $row->getAttribute('level'),
                'tone' => 'accent',
            ],
            'permissions' => [
                'type' => 'text',
                'value' => $permissionCount . ' ' . ($permissionCount === 1 ? 'permission' : 'permissions'),
                'muted' => true,
            ],
            'kind' => $isSystem
                ? ['type' => 'badge', 'value' => 'System', 'tone' => 'warning']
                : ['type' => 'badge', 'value' => 'Custom', 'tone' => 'muted'],
            'users' => [
                'type' => 'text',
                'value' => (string) $userCount . ' ' . ($userCount === 1 ? 'user' : 'users'),
                'muted' => true,
            ],
        ];
    }

    /**
     * @return list<\App\Support\Export\Column>
     */
    public function buildExportColumns(): array
    {
        return [
            Column::make('name', 'Name', static fn (Role $role): string => (string) $role->getAttribute('name')),
            Column::make('slug', 'Slug', static fn (Role $role): string => (string) $role->getAttribute('slug')),
            Column::make('level', 'Level', static fn (Role $role): string => (string) $role->getAttribute('level')),
            Column::make('permissions', 'Permissions', static function (Role $role): string {
                return (string) count($role->permissions());
            }),
            Column::make('kind', 'Type', static function (Role $role): string {
                return (bool) $role->getAttribute('is_system') ? 'System' : 'Custom';
            }),
            Column::make('users', 'Users', fn (Role $role): string => (string) $this->roles->countUsers((int) $role->getKey())),
        ];
    }

    /**
     * @param list<string> $visibleKeys
     * @return list<\App\Support\Export\Column>
     */
    public function resolveExportColumns(array $visibleKeys): array
    {
        $columns = [];
        foreach ($this->buildExportColumns() as $column) {
            if ($visibleKeys === [] || in_array($column->key, $visibleKeys, true)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @return list<array{label:string,url:string,icon:string,format:string,variant:string}>
     */
    public function exports(): array
    {
        return [
            [
                'label' => 'CSV',
                'url' => '/admin/roles/export/csv',
                'icon' => 'file-text',
                'format' => 'csv',
                'variant' => 'secondary',
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function features(): array
    {
        return [
            'search' => true,
            'filter' => true,
            'columns' => true,
            'export' => true,
            'sort' => true,
            'pagination' => true,
            'actions' => true,
            'bulk' => false,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toolbarActions(): array
    {
        return [
            [
                'type' => 'link',
                'label' => 'New role',
                'icon' => 'plus',
                'href' => '/admin/roles/create',
                'variant' => 'primary',
                'permission' => 'roles.manage',
            ],
            [
                'type' => 'link',
                'label' => 'Permissions',
                'icon' => 'key-round',
                'href' => '/admin/permissions',
                'variant' => 'secondary',
            ],
        ];
    }

    public function bulkDeletePath(): ?string
    {
        return null;
    }

    public function bulkStatusPath(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function emptyState(): array
    {
        return [
            'title' => 'No roles found',
            'message' => 'Roles will appear here once seeded or created.',
        ];
    }

    /**
     * @return array{query:string,filter:string,sort:string,direction:string,page:string}
     */
    public function queryParams(): array
    {
        return [
            'query' => 'q',
            'filter' => 'filter',
            'sort' => 'sort',
            'direction' => 'direction',
            'page' => 'page',
        ];
    }
}
