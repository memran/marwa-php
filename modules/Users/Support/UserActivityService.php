<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UserActivityService
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

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:class-string<User>,subjectId:int,details:array<string,mixed>}
     */
    public function createdPayload(User $user, array $afterState): array
    {
        $role = \App\Modules\Auth\Models\Role::findById($afterState['role_id']);
        $roleName = $role !== null ? (string) $role->getAttribute('name') : 'Unknown';

        return [
            'action' => 'user.created',
            'description' => 'Created user ' . $afterState['email'] . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => 'Created user account.',
                'changes' => [
                    'name' => ['before' => null, 'after' => $afterState['name']],
                    'email' => ['before' => null, 'after' => $afterState['email']],
                    'role' => ['before' => null, 'after' => $roleName],
                    'status' => ['before' => null, 'after' => $afterState['is_active'] === 1 ? 'active' : 'disabled'],
                ],
            ],
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:class-string<User>,subjectId:int,details:array<string,mixed>}
     */
    public function updatedPayload(User $user, array $beforeState, array $afterState, bool $passwordChanged): array
    {
        $beforeRole = \App\Modules\Auth\Models\Role::findById($beforeState['role_id'] ?? 0);
        $afterRole = \App\Modules\Auth\Models\Role::findById($afterState['role_id'] ?? 0);

        $before = [
            'name' => $beforeState['name'],
            'email' => $beforeState['email'],
            'role_id' => $beforeState['role_id'] ?? null,
            'role_name' => $beforeRole !== null ? (string) $beforeRole->getAttribute('name') : '',
            'is_active' => $beforeState['is_active'],
        ];

        $after = [
            'name' => $afterState['name'],
            'email' => $afterState['email'],
            'role_id' => $afterState['role_id'],
            'role_name' => $afterRole !== null ? (string) $afterRole->getAttribute('name') : '',
            'is_active' => $afterState['is_active'],
        ];

        return [
            'action' => 'user.updated',
            'description' => 'Updated user ' . $afterState['email'] . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => $this->userUpdateSummary($before, $after, $passwordChanged),
                'changes' => $this->userChanges($before, $after, $passwordChanged),
                'before' => $this->userReadableState($before),
                'after' => $this->userReadableState($after),
            ],
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:class-string<User>,subjectId:int,details:array<string,mixed>}
     */
    public function statusChangedPayload(User $user, array $beforeState, array $afterState): array
    {
        $afterStatus = $afterState['is_active'] === 1 ? 'Active' : 'Disabled';
        $beforeStatus = $beforeState['is_active'] === 1 ? 'Active' : 'Disabled';

        $action = $afterState['is_active'] === 1 ? 'user.enabled' : 'user.disabled';
        $verb = $afterState['is_active'] === 1 ? 'Enabled' : 'Disabled';

        return [
            'action' => $action,
            'description' => $verb . ' user ' . $afterState['email'] . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => $verb . ' user account.',
                'changes' => [
                    'Status' => [
                        'before' => $beforeStatus,
                        'after' => $afterStatus,
                    ],
                ],
                'before' => [
                    'Status' => $beforeStatus,
                ],
                'after' => [
                    'Status' => $afterStatus,
                ],
            ],
        ];
    }

    /**
     * @return array{action:string,description:string,subjectType:class-string<User>,subjectId:int,details:array<string,mixed>}
     */
    public function deletedPayload(User $user): array
    {
        return [
            'action' => 'user.deleted',
            'description' => 'Deleted user ' . (string) $user->getAttribute('email') . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => 'Soft deleted user account.',
                'state' => $this->userReadableState($this->userSnapshot($user)),
            ],
        ];
    }

    /**
     * @return array{action:string,description:string,subjectType:class-string<User>,subjectId:int,details:array<string,mixed>}
     */
    public function restoredPayload(User $user): array
    {
        return [
            'action' => 'user.restored',
            'description' => 'Restored user ' . (string) $user->getAttribute('email') . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => 'Restored user account.',
                'state' => $this->userReadableState($this->userSnapshot($user)),
            ],
        ];
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
