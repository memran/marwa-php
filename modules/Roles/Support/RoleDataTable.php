<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Role;
use App\Support\Datatables\Action;
use App\Support\Datatables\Column;
use App\Support\Datatables\DataTable;
use App\Support\Datatables\Filter;
use Psr\Http\Message\ServerRequestInterface;

final class RoleDataTable
{
    public function make(ServerRequestInterface $request): DataTable
    {
        return DataTable::fromRequest($request)
            ->query(Role::query()->with('permissionsRelation')->withCount('usersRelation as users_count', 'permissionsRelation as permissions_count'))
            ->title('Roles')
            ->description('Manage user roles and their permissions from one place.')
            ->searchPlaceholder('Search roles...')
            ->searchAriaLabel('Search roles')
            ->searchParameter('q')
            ->sortParameter('sort')
            ->directionParameter('direction')
            ->pageParameter('page')
            ->columnsParameter('columns')
            ->selectedIdsParameter('ids')
            ->path('/admin/roles')
            ->rowKey('id')
            ->defaultSort('level', 'desc')
            ->columns([
                Column::make('name')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->link(static fn (Role $role): string => '/admin/roles/' . $role->getKey() . '/edit'),
                Column::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                Column::make('level')
                    ->label('Level')
                    ->sortable(),
                Column::make('permissions_count')
                    ->label('Permissions')
                    ->format(static fn (mixed $value): string => ((int) $value) . ' ' . (((int) $value) === 1 ? 'permission' : 'permissions')),
                Column::make('is_system')
                    ->label('Type')
                    ->format(static fn (mixed $value): string => (bool) $value ? 'System' : 'Custom')
                    ->badge(static fn (mixed $value): array => (bool) $value
                        ? ['tone' => 'warning', 'label' => 'System']
                        : ['tone' => 'muted', 'label' => 'Custom']),
                Column::make('users_count')
                    ->label('Users')
                    ->sortable('users_count')
                    ->format(static fn (mixed $value): string => ((int) $value) . ' ' . (((int) $value) === 1 ? 'user' : 'users')),
            ])
            ->filters([
                Filter::select('type')
                    ->label('Type')
                    ->options([
                        'all' => 'All roles',
                        'system' => 'System roles',
                        'custom' => 'Custom roles',
                    ])
                    ->default('all')
                    ->apply(static function ($query, mixed $value): void {
                        $filter = is_string($value) ? trim($value) : '';

                        if ($filter === 'system') {
                            $query->where('is_system', '=', 1);
                            return;
                        }

                        if ($filter === 'custom') {
                            $query->where('is_system', '=', 0);
                        }
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->permission('roles.manage')
                    ->url(static fn (Role $role): string => '/admin/roles/' . $role->getKey() . '/edit'),
                Action::make('delete')
                    ->label('Delete')
                    ->permission('roles.manage')
                    ->variant('danger')
                    ->confirm('Delete this role?')
                    ->url(static fn (Role $role): string => '/admin/roles/' . $role->getKey() . '/delete')
                    ->visible(static fn (Role $role): bool => !(bool) $role->getAttribute('is_system') && (int) ($role->getAttribute('users_count') ?? 0) === 0),
            ])
            ->row(static function (Role $role): array {
                $isSystem = (bool) $role->getAttribute('is_system');
                $hasUsers = (int) ($role->getAttribute('users_count') ?? 0) > 0;

                return [
                    'bulk' => [
                        'disabled' => $isSystem || $hasUsers,
                        'title' => (string) $role->getAttribute('name'),
                        'label' => 'Select ' . (string) $role->getAttribute('name'),
                    ],
                    'cells' => [
                        'is_system' => $isSystem
                            ? ['type' => 'badge', 'value' => 'System', 'badge' => ['tone' => 'warning', 'label' => 'System']]
                            : ['type' => 'badge', 'value' => 'Custom', 'badge' => ['tone' => 'muted', 'label' => 'Custom']],
                    ],
                ];
            });
    }
}
