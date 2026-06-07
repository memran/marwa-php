<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\DataTable\DataTableConfigInterface;
use App\Support\DataTable\DataTableOptionsInterface;
use App\Support\DataTable\DataTableRowActions;
use App\Support\Export\Column;

final class UserDataTable implements DataTableConfigInterface, DataTableOptionsInterface
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthManager $auth,
        private readonly DataTableRowActions $actions,
    ) {
    }

    public function pageTitle(): string
    {
        return 'User accounts';
    }

    public function pageDescription(): string
    {
        return 'Search, filter, sort, and inspect users from one reusable table.';
    }

    public function searchPlaceholder(): string
    {
        return 'Search users...';
    }

    public function columnOptions(): array
    {
        return [
            'name' => 'User',
            'email' => 'Email',
            'role' => 'Role',
            'is_active' => 'Status',
        ];
    }

    public function sortableKeys(): array
    {
        return ['name', 'email', 'role', 'is_active'];
    }

    public function basePath(): string
    {
        return '/admin/users';
    }

    public function defaultSort(): string
    {
        return 'created_at';
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
        return $row instanceof User ? (string) $row->getKey() : '';
    }

    public function rowIsTrashed(mixed $row): bool
    {
        return $row instanceof User && trim((string) $row->getAttribute('deleted_at')) !== '';
    }

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

    public function statusOptions(): array
    {
        return [
            ['value' => 'all', 'label' => 'All'],
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'disabled', 'label' => 'Disabled'],
            ['value' => 'trashed', 'label' => 'Trashed'],
        ];
    }

    public function hiddenFields(array $params, array $visibleColumns): array
    {
        $fields = [];
        foreach ($params as $name => $value) {
            $name = $name === 'status' ? 'filter' : (string) $name;
            if ($value !== null && $value !== '') {
                $fields[] = ['name' => $name, 'value' => is_array($value) ? implode(',', $value) : (string) $value];
            }
        }

        foreach ($visibleColumns as $column) {
            $fields[] = ['name' => 'columns[]', 'value' => $column];
        }

        return $fields;
    }

    public function buildRow(mixed $row): array
    {
        if (!$row instanceof User) {
            return ['bulk' => [], 'cells' => [], 'actions' => []];
        }

        $isTrashed = $this->rowIsTrashed($row);
        $protectedId = $this->users->protectedAdminId();
        $isProtected = $protectedId !== null && (int) $row->getKey() === $protectedId;
        $authUser = $this->auth->user();
        $isActiveSession = $authUser instanceof User && (int) $authUser->getKey() === (int) $row->getKey();

        return [
            'bulk' => $this->rowBulkMeta($row, $isProtected, $isTrashed, $isActiveSession),
            'cells' => $this->buildCells($row, $isProtected),
            'actions' => $this->buildRowActions($row, $isTrashed, $isProtected),
        ];
    }

    public function buildRowActions(mixed $row, bool $isTrashed, bool $isProtected): array
    {
        if (!$row instanceof User) {
            return [];
        }

        $id = (string) $row->getKey();
        $actions = [
            $this->actions->link('View', '/admin/users/' . $id, 'secondary', 'users.view'),
        ];

        if (!$isTrashed) {
            $actions[] = $this->actions->link('Edit', '/admin/users/' . $id . '/edit', 'secondary', 'users.edit');
        }

        if (!$isTrashed && !$isProtected) {
            $actions[] = $this->actions->formButton(
                'Delete',
                '/admin/users/' . $id . '/delete',
                'danger',
                'trash-2',
                'users.delete',
                'Delete this user?'
            );
        }

        return $actions;
    }

    public function rowBulkMeta(mixed $row, bool $isProtected, bool $isTrashed, bool $isActiveSession): array
    {
        if (!$row instanceof User) {
            return [];
        }

        return [
            'id' => (string) $row->getKey(),
            'disabled' => $isProtected || $isTrashed || $isActiveSession,
            'title' => 'Select ' . (string) $row->getAttribute('name'),
            'label' => 'Select ' . (string) $row->getAttribute('name'),
        ];
    }

    public function buildCells(mixed $row, bool $isProtected): array
    {
        if (!$row instanceof User) {
            return [];
        }

        $role = $row->role();

        return [
            'name' => [
                'type' => 'avatar_link',
                'value' => (string) $row->getAttribute('name'),
                'meta' => 'ID ' . (string) $row->getKey(),
                'avatar' => (string) $row->getAttribute('name'),
                'href' => '/admin/users/' . (string) $row->getKey(),
            ],
            'email' => ['type' => 'text', 'value' => (string) $row->getAttribute('email'), 'muted' => true],
            'role' => [
                'type' => 'badge_stack',
                'items' => [[
                    'value' => $role !== null ? (string) $role->getAttribute('name') : 'Unknown',
                    'tone' => 'muted',
                    'icon' => 'shield',
                ]],
            ],
            'is_active' => $this->statusCell($row, $isProtected),
        ];
    }

    public function buildExportColumns(): array
    {
        return [
            Column::make('name', 'Name', static fn (User $user): string => (string) $user->getAttribute('name')),
            Column::make('email', 'Email', static fn (User $user): string => (string) $user->getAttribute('email')),
            Column::make('role', 'Role', static function (User $user): string {
                $role = $user->role();
                return $role !== null ? (string) $role->getAttribute('name') : 'Unknown';
            }),
            Column::make('is_active', 'Status', fn (User $user): string => $this->statusLabel($user, false)),
        ];
    }

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

    public function exports(): array
    {
        return [
            [
                'label' => 'CSV',
                'url' => '/admin/users/export/csv',
                'icon' => 'file-text',
                'format' => 'csv',
                'variant' => 'secondary',
            ],
            [
                'label' => 'PDF',
                'url' => '/admin/users/export/pdf',
                'icon' => 'file-down',
                'format' => 'pdf',
                'variant' => 'secondary',
            ],
        ];
    }

    public function features(): array
    {
        return [
            'bulk' => true,
            'export' => true,
        ];
    }

    public function toolbarActions(): array
    {
        return [[
            'type' => 'button',
            'label' => 'Print',
            'icon' => 'printer',
            'onclick' => 'window.print()',
            'title' => 'Print this page',
            'variant' => 'secondary',
        ]];
    }

    public function bulkDeletePath(): ?string
    {
        $path = config('users.bulk_delete_path', '/admin/users/bulk-delete');

        return is_string($path) && trim($path) !== '' ? $path : null;
    }

    public function bulkStatusPath(): ?string
    {
        $path = config('users.bulk_status_path', '/admin/users/bulk-status');

        return is_string($path) && trim($path) !== '' ? $path : null;
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No users found',
            'message' => 'Adjust filters or create a user account to get started.',
        ];
    }

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

    /**
     * @return array<string, mixed>
     */
    private function statusCell(User $user, bool $isProtected): array
    {
        if ($this->rowIsTrashed($user)) {
            return ['type' => 'badge', 'value' => 'Trashed', 'tone' => 'danger'];
        }

        if ($isProtected) {
            return [
                'type' => 'badge_stack',
                'items' => [
                    ['value' => 'Active', 'tone' => 'success'],
                    ['value' => 'Protected', 'tone' => 'warning'],
                ],
            ];
        }

        return (int) $user->getAttribute('is_active') === 1
            ? ['type' => 'badge', 'value' => 'Active', 'tone' => 'success']
            : ['type' => 'badge', 'value' => 'Disabled', 'tone' => 'warning'];
    }

    private function statusLabel(User $user, bool $isProtected): string
    {
        if ($this->rowIsTrashed($user)) {
            return 'Trashed';
        }

        if ($isProtected) {
            return 'Active, Protected';
        }

        return (int) $user->getAttribute('is_active') === 1 ? 'Active' : 'Disabled';
    }
}
