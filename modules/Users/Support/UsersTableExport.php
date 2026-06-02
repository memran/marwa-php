<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use App\Support\Export\Column;
use App\Support\Export\CsvExporter;
use App\Support\Export\Exporter;
use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\TableHtmlBuilder;
use App\Support\Export\PdfExporter;

final class UsersTableExport
{
    public function csv(): Exporter
    {
        return new CsvExporter();
    }

    public function pdf(): Exporter
    {
        return new PdfExporter(new DompdfGenerator(), new TableHtmlBuilder());
    }

    /**
     * @return list<Column>
     */
    public function columns(): array
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
     * @return array<string, string>
     */
    public function columnOptions(): array
    {
        $options = [];
        foreach ($this->columns() as $column) {
            $options[$column->key] = $column->label;
        }

        return $options;
    }

    /**
     * @param list<string> $visibleKeys
     * @return list<Column>
     */
    public function resolveColumns(array $visibleKeys): array
    {
        $available = [];
        foreach ($this->columns() as $column) {
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
}
