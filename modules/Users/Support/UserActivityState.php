<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UserActivityState
{
    /**
     * @return array{name: string, email: string, role_id: int|null, role_name: string, is_active: int}
     */
    public function userSnapshot(User $user): array
    {
        $role = $user->role();
        return [
            'name' => trim((string) $user->getAttribute('name')),
            'email' => trim((string) $user->getAttribute('email')),
            'role_id' => $role !== null ? (int) $role->getKey() : null,
            'role_name' => $role !== null ? (string) $role->getAttribute('name') : '',
            'is_active' => (int) (bool) $user->getAttribute('is_active'),
        ];
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $before
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $after
     */
    public function userStateHasChanges(array $before, array $after): bool
    {
        return $before !== $after;
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $state
     * @return array<string, string>
     */
    public function userReadableState(array $state): array
    {
        return [
            'Name' => $state['name'] !== '' ? $state['name'] : 'Unknown',
            'Email' => $state['email'] !== '' ? $state['email'] : 'Unknown',
            'Role' => $state['role_name'] !== '' ? $state['role_name'] : 'Unknown',
            'Status' => $state['is_active'] === 1 ? 'Active' : 'Disabled',
        ];
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $before
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $after
     * @return array<string, array{before: string, after: string}>
     */
    public function userChanges(array $before, array $after, bool $passwordChanged): array
    {
        $changes = [];

        foreach (['name', 'email', 'role_name', 'is_active'] as $field) {
            if ($before[$field] !== $after[$field]) {
                $changes[$this->fieldLabel($field)] = [
                    'before' => $this->fieldValue($field, $before[$field]),
                    'after' => $this->fieldValue($field, $after[$field]),
                ];
            }
        }

        if ($passwordChanged) {
            $changes['Password'] = [
                'before' => 'Unchanged',
                'after' => 'Updated',
            ];
        }

        return $changes;
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $before
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $after
     */
    public function userUpdateSummary(array $before, array $after, bool $passwordChanged): string
    {
        $labels = array_keys($this->userChanges($before, $after, $passwordChanged));

        if ($labels === []) {
            return 'No fields changed.';
        }

        return 'Changed fields: ' . implode(', ', $labels) . '.';
    }

    private function fieldLabel(string $field): string
    {
        return $field === 'is_active' ? 'Status' : ucfirst($field);
    }

    private function fieldValue(string $field, mixed $value): string
    {
        if ($field === 'is_active') {
            return (int) $value === 1 ? 'Active' : 'Disabled';
        }

        return (string) $value;
    }
}
