<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\PermissionRepository;
use App\Support\Datatables\Action;
use App\Support\Datatables\Column;
use App\Support\Datatables\DataTable;
use App\Support\Datatables\Filter;
use Psr\Http\Message\ServerRequestInterface;

final class PermissionDataTable
{
    public function __construct(
        private readonly PermissionRepository $permissions,
    ) {}

    public function make(ServerRequestInterface $request): DataTable
    {
        return DataTable::fromRequest($request)
            ->query(Permission::query())
            ->title('Permissions')
            ->description('Search, filter, and manage permission records from one reusable table.')
            ->searchPlaceholder('Search permissions...')
            ->searchAriaLabel('Search permissions')
            ->searchParameter('q')
            ->sortParameter('sort')
            ->directionParameter('direction')
            ->pageParameter('page')
            ->columnsParameter('columns')
            ->path('/admin/permissions')
            ->rowKey('id')
            ->defaultSort('group', 'asc')
            ->columns([
                Column::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable()
                    ->link(static fn (Permission $permission): string => '/admin/permissions/' . $permission->getKey() . '/edit'),
                Column::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),
                Column::make('group')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge(static fn (mixed $value): array => [
                        'tone' => 'accent',
                        'label' => (string) $value,
                    ]),
                Column::make('description')
                    ->label('Description')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Filter::select('group')
                    ->label('Group')
                    ->options($this->groupOptions())
                    ->default('all')
                    ->apply(static function ($query, mixed $value): void {
                        $group = is_string($value) ? trim($value) : '';

                        if ($group !== '' && $group !== 'all') {
                            $query->where('group', '=', $group);
                        }
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->permission('permissions.manage')
                    ->url(static fn (Permission $permission): string => '/admin/permissions/' . $permission->getKey() . '/edit'),
                Action::make('delete')
                    ->label('Delete')
                    ->permission('permissions.manage')
                    ->variant('danger')
                    ->confirm('Delete this permission?')
                    ->url(static fn (Permission $permission): string => '/admin/permissions/' . $permission->getKey() . '/delete'),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function groupOptions(): array
    {
        $options = ['all' => 'All groups'];

        foreach ($this->permissions->groupNames() as $group) {
            $options[$group] = ucfirst(str_replace(['_', '-'], ' ', $group));
        }

        return $options;
    }
}
