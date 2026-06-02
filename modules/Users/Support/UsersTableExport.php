<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UsersTableExport
{
    /**
     * @param list<User> $users
     * @return list<array<string, mixed>>
     */
    public function buildCsv(array $users, array $columns): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        $this->writeCsvHeader($handle, $columns);

        foreach ($users as $user) {
            $this->writeCsvRow($handle, $user, $columns);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param resource $handle
     * @param list<string> $columns
     */
    private function writeCsvHeader($handle, array $columns): void
    {
        $labels = $this->columnOptions();
        fputcsv($handle, array_map(
            static fn (string $column): string => $labels[$column] ?? $column,
            $columns
        ));
    }

    /**
     * @param resource $handle
     * @param list<string> $columns
     */
    private function writeCsvRow($handle, User $user, array $columns): void
    {
        $row = [];

        foreach ($columns as $column) {
            $row[] = $this->exportColumnValue($user, $column);
        }

        fputcsv($handle, $row);
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

    private function exportColumnValue(User $user, string $column): string
    {
        return match ($column) {
            'name' => (string) $user->getAttribute('name'),
            'email' => (string) $user->getAttribute('email'),
            'role' => $this->exportRoleLabel($user),
            'status' => $this->exportStatusLabel($user),
            'last_login' => (string) ($user->getAttribute('last_login_at') ?: 'Never'),
            default => '',
        };
    }

    private function exportRoleLabel(User $user): string
    {
        $role = $user->role();
        return $role === null ? 'Unknown' : (string) $role->getAttribute('name');
    }

    private function exportStatusLabel(User $user): string
    {
        if (!empty($user->getAttribute('deleted_at'))) {
            return 'Trashed';
        }
        return (bool) $user->getAttribute('is_active') ? 'Active' : 'Disabled';
    }
}
