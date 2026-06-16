<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\Datatables\Action;
use App\Support\Datatables\BulkAction;
use App\Support\Datatables\Column;
use App\Support\Datatables\DataTable;
use App\Support\Datatables\Filter;
use App\Support\Export\Column as ExportColumn;
use Psr\Http\Message\ServerRequestInterface;

final class UserDataTable
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthManager $auth,
    ) {}

    public function make(ServerRequestInterface $request): DataTable
    {
        $protectedId = $this->users->protectedAdminId();
        $authUser = $this->auth->user();

        return DataTable::fromRequest($request)
            ->query(User::query()->with('roleRelation'))
            ->title('User accounts')
            ->description('Search, filter, sort, and inspect users from one reusable table.')
            ->searchPlaceholder('Search users...')
            ->searchAriaLabel('Search users')
            ->searchParameter('q')
            ->sortParameter('sort')
            ->directionParameter('direction')
            ->pageParameter('page')
            ->columnsParameter('columns')
            ->selectedIdsParameter('ids')
            ->path('/admin/users')
            ->rowKey('id')
            ->defaultSort('created_at', 'desc')
            ->bulkDeleteUrl('/admin/users/bulk-delete')
            ->bulkStatusUrl('/admin/users/bulk-status')
            ->bulkStatusOptions([
                ['value' => 'active', 'label' => 'Activate'],
                ['value' => 'disabled', 'label' => 'Disable'],
            ])
            ->columns([
                Column::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->link(static fn (User $user): string => '/admin/users/' . $user->getKey()),
                Column::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Column::make('roleRelation.name')
                    ->label('Role')
                    ->sortable('role_id'),
                Column::make('is_active')
                    ->label('Status')
                    ->filterable()
                    ->format(static fn (mixed $value): string => (int) $value === 1 ? 'Active' : 'Disabled')
                    ->badge(static fn (mixed $value): array => (int) $value === 1
                        ? ['tone' => 'success', 'label' => 'Active']
                        : ['tone' => 'warning', 'label' => 'Disabled']),
                Column::make('created_at')
                    ->label('Created')
                    ->sortable(),
            ])
            ->filters([
                Filter::select('status')
                    ->label('Status')
                    ->options([
                        'all' => 'All',
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'trashed' => 'Trashed',
                    ])
                    ->default('all')
                    ->apply(static function ($query, mixed $value): void {
                        $status = UserStatus::tryFromFilter(is_string($value) ? $value : null);

                        if ($status === UserStatus::Active) {
                            $query->where('is_active', '=', 1);
                            return;
                        }

                        if ($status === UserStatus::Disabled) {
                            $query->where('is_active', '=', 0);
                            return;
                        }

                        if ($status === UserStatus::Trashed) {
                            $query->onlyTrashed();
                        }
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->permission('users.view')
                    ->url(static fn (User $user): string => '/admin/users/' . $user->getKey()),
                Action::make('edit')
                    ->label('Edit')
                    ->permission('users.edit')
                    ->url(static fn (User $user): string => '/admin/users/' . $user->getKey() . '/edit')
                    ->visible(static fn (User $user) => $protectedId === null || (int) $user->getKey() !== $protectedId),
                Action::make('delete')
                    ->label('Delete')
                    ->variant('danger')
                    ->permission('users.delete')
                    ->confirm('Delete this user?')
                    ->url(static fn (User $user): string => '/admin/users/' . $user->getKey() . '/delete')
                    ->visible(static function (User $user) use ($protectedId, $authUser): bool {
                        if (trim((string) $user->getAttribute('deleted_at')) !== '') {
                            return false;
                        }

                        if ($protectedId !== null && (int) $user->getKey() === $protectedId) {
                            return false;
                        }

                        return !($authUser instanceof User && (int) $authUser->getKey() === (int) $user->getKey());
                    }),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete selected')
                    ->variant('danger')
                    ->href('/admin/users/bulk-delete')
                    ->confirm('Delete the selected users?'),
                BulkAction::make('status')
                    ->label('Update status')
                    ->href('/admin/users/bulk-status'),
            ])
            ->exports([
                [
                    'format' => 'csv',
                    'label' => 'CSV',
                    'title' => 'Download as CSV',
                    'url' => '/admin/users/export/csv',
                ],
                [
                    'format' => 'pdf',
                    'label' => 'PDF',
                    'title' => 'Download as PDF',
                    'url' => '/admin/users/export/pdf',
                ],
            ])
            ->row(static function (User $user) use ($protectedId, $authUser): array {
                $isTrashed = trim((string) $user->getAttribute('deleted_at')) !== '';
                $isProtected = $protectedId !== null && (int) $user->getKey() === $protectedId;
                $isActiveSession = $authUser instanceof User && (int) $authUser->getKey() === (int) $user->getKey();

                $row = [
                    'bulk' => [
                        'disabled' => $isTrashed || $isProtected || $isActiveSession,
                        'title' => 'Select ' . (string) $user->getAttribute('name'),
                        'label' => 'Select ' . (string) $user->getAttribute('name'),
                    ],
                ];

                if ($isTrashed) {
                    $row['cells']['is_active'] = [
                        'type' => 'badge',
                        'value' => 'Trashed',
                        'badge' => ['tone' => 'danger', 'label' => 'Trashed'],
                    ];
                } elseif ($isProtected) {
                    $row['cells']['is_active'] = [
                        'type' => 'badge_stack',
                        'items' => [
                            ['value' => 'Active', 'tone' => 'success'],
                            ['value' => 'Protected', 'tone' => 'warning'],
                        ],
                    ];
                }

                return $row;
            });
    }

    /**
     * @return list<ExportColumn>
     */
    public function exportColumns(): array
    {
        return [
            ExportColumn::make('name', 'Name', static fn (User $user): string => (string) $user->getAttribute('name')),
            ExportColumn::make('email', 'Email', static fn (User $user): string => (string) $user->getAttribute('email')),
            ExportColumn::make('role', 'Role', static function (User $user): string {
                $role = $user->role();

                return $role !== null ? (string) $role->getAttribute('name') : 'Unknown';
            }),
            ExportColumn::make('is_active', 'Status', static function (User $user): string {
                if (trim((string) $user->getAttribute('deleted_at')) !== '') {
                    return 'Trashed';
                }

                return (int) $user->getAttribute('is_active') === 1 ? 'Active' : 'Disabled';
            }),
        ];
    }

    /**
     * @param list<string>|string $requested
     * @return list<ExportColumn>
     */
    public function resolveExportColumns(array|string $requested): array
    {
        $keys = is_string($requested)
            ? array_filter(array_map('trim', explode(',', $requested)), static fn (string $value): bool => $value !== '')
            : $requested;

        $allowed = [];
        foreach ($this->exportColumns() as $column) {
            $allowed[$column->key] = $column;
        }

        if ($keys === []) {
            return array_values($allowed);
        }

        $visible = [];
        foreach ($keys as $key) {
            if (isset($allowed[$key])) {
                $visible[] = $allowed[$key];
            }
        }

        return $visible === [] ? array_values($allowed) : $visible;
    }
}
