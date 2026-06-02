<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\DataTable\DataTableConfigInterface;
use App\Support\DataTable\DataTableRowActions;
use App\Support\Export\Column;

final class UsersTableConfig implements DataTableConfigInterface
{
    public function __construct(
        private readonly UserAccessPolicy $access,
        private readonly AuthManager $auth,
        private readonly DataTableRowActions $rowActions,
    ) {
    }

    public function pageTitle(): string
    {
        return 'Registered users';
    }

    public function pageDescription(): string
    {
        return 'Search, filter, and review access at a glance.';
    }

    public function searchPlaceholder(): string
    {
        return 'Search anything...';
    }

    /**
     * @return array<string, string>
     */
    public function columnOptions(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'last_login' => 'Last login',
        ];
    }

    /**
     * @return list<string>
     */
    public function sortableKeys(): array
    {
        return ['name', 'email', 'role', 'last_login'];
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
        if ($row instanceof User) {
            return (string) $row->getKey();
        }
        if (is_array($row) && isset($row['id'])) {
            return (string) $row['id'];
        }
        return '';
    }

    public function rowIsTrashed(mixed $row): bool
    {
        if ($row instanceof User) {
            return !empty($row->getAttribute('deleted_at'));
        }
        if (is_array($row)) {
            return !empty($row['deleted_at']);
        }
        return false;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array{label:string,href:string,active:bool}>
     */
    public function filterItems(array $state, array $visibleColumns, callable $buildUrl): array
    {
        $items = [];
        foreach (UserStatus::cases() as $status) {
            $items[] = [
                'label' => $status->label(),
                'href' => $buildUrl([
                    'query' => $state['query'],
                    'filter' => $status->value,
                    'sort' => $state['sort'],
                    'direction' => $state['direction'],
                    'page' => 1,
                ]),
                'active' => $state['filter'] === $status->value,
            ];
        }
        return $items;
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    public function statusOptions(): array
    {
        return [
            ['value' => UserStatus::Active->value, 'label' => 'Mark active'],
            ['value' => UserStatus::Disabled->value, 'label' => 'Mark disabled'],
        ];
    }

    /**
     * @param array<string, string|int|list<string>|null> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
     */
    public function hiddenFields(array $params, array $visibleColumns = []): array
    {
        $fields = $this->hiddenParamFields($params);

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
        $user = $row instanceof User ? $row : null;
        $protectedAdminId = $this->access->protectedAdminId();
        $isProtected = $user !== null
            && $protectedAdminId !== null
            && (int) $user->getKey() === (int) $protectedAdminId;
        $isTrashed = $this->rowIsTrashed($row);
        $isActiveSession = $user !== null && $this->access->isActiveSessionUser($user, $this->auth);

        return [
            'bulk' => $this->rowBulkMeta($row, $isProtected, $isTrashed, $isActiveSession),
            'cells' => $this->buildCells($row, $isProtected),
            'actions' => $this->buildRowActions($row, $isTrashed, $isProtected),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildRowActions(mixed $row, bool $isTrashed, bool $isProtected): array
    {
        if (!($row instanceof User)) {
            return [];
        }

        return $isTrashed
            ? $this->trashedActions($row)
            : $this->activeActions($row, $isProtected);
    }

    /**
     * @return array<string, mixed>
     */
    public function rowBulkMeta(mixed $row, bool $isProtected, bool $isTrashed, bool $isActiveSession): array
    {
        $key = $this->rowKey($row);
        $name = '';
        if ($row instanceof User) {
            $name = (string) $row->getAttribute('name');
        } elseif (is_array($row) && isset($row['name'])) {
            $name = (string) $row['name'];
        }

        return [
            'id' => $key,
            'disabled' => $isProtected || $isTrashed || $isActiveSession,
            'title' => $this->bulkTitleFor($isProtected, $isTrashed, $isActiveSession),
            'label' => 'Select ' . $name,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCells(mixed $row, bool $isProtected): array
    {
        if (!($row instanceof User)) {
            return [];
        }

        return [
            'name' => $this->nameCell($row),
            'email' => $this->emailCell($row),
            'role' => $this->roleCell($row),
            'status' => $this->statusCell($row, $isProtected),
            'last_login' => $this->lastLoginCell($row),
        ];
    }

    /**
     * @return list<\App\Support\Export\Column>
     */
    public function buildExportColumns(): array
    {
        return [
            Column::make('name', 'Name', static fn (User $user): string => (string) $user->getAttribute('name')),
            Column::make('email', 'Email', static fn (User $user): string => (string) $user->getAttribute('email')),
            Column::make('role', 'Role', fn (User $user): string => $this->roleLabel($user)),
            Column::make('status', 'Status', fn (User $user): string => $this->statusLabel($user)),
            Column::make(
                'last_login',
                'Last login',
                static fn (User $user): string => (string) ($user->getAttribute('last_login_at') ?: 'Never')
            ),
        ];
    }

    /**
     * @param list<string> $visibleKeys
     * @return list<\App\Support\Export\Column>
     */
    public function resolveExportColumns(array $visibleKeys): array
    {
        $available = [];
        foreach ($this->buildExportColumns() as $column) {
            $available[$column->key] = $column;
        }

        if ($visibleKeys === []) {
            return array_values($available);
        }

        $resolved = [];
        foreach ($visibleKeys as $key) {
            if (isset($available[$key])) {
                $resolved[] = $available[$key];
            }
        }

        return $resolved === [] ? array_values($available) : $resolved;
    }

    /**
     * @return list<array{label:string,url:string,icon:string,format:string,variant:string}>
     */
    public function exports(): array
    {
        return [
            [
                'label' => 'CSV',
                'url' => $this->basePath() . '/export',
                'icon' => 'file-text',
                'format' => 'csv',
                'variant' => 'secondary',
            ],
            [
                'label' => 'PDF',
                'url' => $this->basePath() . '/export.pdf',
                'icon' => 'file',
                'format' => 'pdf',
                'variant' => 'secondary',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trashedActions(User $user): array
    {
        return [
            $this->rowActions->link('Profile', '/admin/users/' . $user->getKey(), 'ghost'),
            $this->rowActions->formButton('Restore', '/admin/users/' . $user->getKey() . '/restore', 'secondary', 'rotate-ccw', 'users.restore'),
            $this->rowActions->disabledButton('Delete', 'Restored users can be edited or deleted after restore.'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activeActions(User $user, bool $isProtected): array
    {
        $actions = [
            $this->rowActions->link('Profile', '/admin/users/' . $user->getKey(), 'ghost'),
            $this->rowActions->link('Edit', '/admin/users/' . $user->getKey() . '/edit', 'secondary', 'users.edit'),
        ];

        if ($isProtected) {
            $actions[] = $this->rowActions->disabledButton('Delete', 'The last admin user cannot be deleted.');
            return $actions;
        }

        $actions[] = $this->rowActions->formButton(
            'Delete',
            '/admin/users/' . $user->getKey() . '/delete',
            'danger',
            'trash-2',
            'users.delete',
            'Delete this user?',
        );
        return $actions;
    }

    /**
     * @return array<string, mixed>
     */
    private function nameCell(User $user): array
    {
        return [
            'type' => 'avatar_link',
            'value' => (string) $user->getAttribute('name'),
            'href' => '/admin/users/' . $user->getKey(),
            'avatar' => (string) $user->getAttribute('name'),
            'meta' => 'ID ' . (string) $user->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailCell(User $user): array
    {
        return [
            'type' => 'text',
            'value' => (string) $user->getAttribute('email'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function roleCell(User $user): array
    {
        $role = $user->role();
        return [
            'type' => 'badge',
            'value' => $role === null ? 'Unknown' : (string) $role->getAttribute('name'),
            'tone' => 'accent',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statusCell(User $user, bool $isProtected): array
    {
        return [
            'type' => 'badge_stack',
            'items' => $this->buildStatusBadges($user, $isProtected),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lastLoginCell(User $user): array
    {
        return [
            'type' => 'text',
            'value' => (string) ($user->getAttribute('last_login_at') ?: 'Never'),
            'muted' => true,
        ];
    }

    /**
     * @return list<array{value:string,tone:string,icon?:string}>
     */
    public function buildStatusBadges(User $user, bool $isProtected): array
    {
        $badges = [];

        if (!empty($user->getAttribute('deleted_at'))) {
            $badges[] = ['value' => 'Trashed', 'tone' => 'danger', 'icon' => 'trash-2'];
        } elseif ((bool) $user->getAttribute('is_active')) {
            $badges[] = ['value' => 'Active', 'tone' => 'success'];
        } else {
            $badges[] = ['value' => 'Disabled', 'tone' => 'warning'];
        }

        if ($isProtected) {
            $badges[] = ['value' => 'Protected', 'tone' => 'warning', 'icon' => 'shield'];
        }

        return $badges;
    }

    private function bulkTitleFor(bool $isProtected, bool $isTrashed, bool $isActiveSession): string
    {
        if ($isProtected) {
            return 'The last admin user cannot be selected for bulk actions.';
        }
        if ($isTrashed) {
            return 'Trashed users cannot be selected for bulk actions.';
        }
        if ($isActiveSession) {
            return 'The active session user cannot be selected for bulk actions.';
        }
        return 'Select user for bulk actions.';
    }

    private function roleLabel(User $user): string
    {
        $role = $user->role();
        return $role === null ? 'Unknown' : (string) $role->getAttribute('name');
    }

    private function statusLabel(User $user): string
    {
        if (!empty($user->getAttribute('deleted_at'))) {
            return 'Trashed';
        }
        return (bool) $user->getAttribute('is_active') ? 'Active' : 'Disabled';
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array{name:string,value:string}>
     */
    private function hiddenParamFields(array $params): array
    {
        $fields = [];
        foreach ($params as $name => $value) {
            if ($value !== null && $value !== '') {
                $fields[] = ['name' => (string) $name, 'value' => (string) $value];
            }
        }
        return $fields;
    }
}
