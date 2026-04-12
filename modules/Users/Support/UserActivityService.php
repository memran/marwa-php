<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Users\Models\User;

final class UserActivityService
{
    /**
     * @return array{name: string, email: string, role: string, is_active: int}
     */
    public function userSnapshot(User $user): array
    {
        return [
            'name' => trim((string) $user->getAttribute('name')),
            'email' => trim((string) $user->getAttribute('email')),
            'role' => trim((string) $user->getAttribute('role')),
            'is_active' => (int) (bool) $user->getAttribute('is_active'),
        ];
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
     */
    public function userStateHasChanges(array $before, array $after): bool
    {
        return $before !== $after;
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $state
     * @return array<string, string>
     */
    public function userReadableState(array $state): array
    {
        return [
            'Name' => $state['name'] !== '' ? $state['name'] : 'Unknown',
            'Email' => $state['email'] !== '' ? $state['email'] : 'Unknown',
            'Role' => $state['role'] !== '' ? $state['role'] : 'Unknown',
            'Status' => $state['is_active'] === 1 ? 'Active' : 'Disabled',
        ];
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
     * @return array<string, array{before: string, after: string}>
     */
    public function userChanges(array $before, array $after, bool $passwordChanged): array
    {
        $changes = [];

        foreach (['name', 'email', 'role', 'is_active'] as $field) {
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
     * @param array{name: string, email: string, role: string, is_active: int} $before
     * @param array{name: string, email: string, role: string, is_active: int} $after
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
     * @param array{name: string, email: string, role: string, is_active: int} $afterState
     */
    public function recordCreated(User $user, array $afterState, ?User $actor): void
    {
        (new ActivityRecorder())->recordActorAction(
            'user.created',
            'Created user ' . $afterState['email'] . '.',
            $actor,
            User::class,
            (int) $user->getKey(),
            [
                'summary' => 'Created user account.',
                'changes' => [
                    'name' => ['before' => null, 'after' => $afterState['name']],
                    'email' => ['before' => null, 'after' => $afterState['email']],
                    'role' => ['before' => null, 'after' => $afterState['role']],
                    'status' => ['before' => null, 'after' => $afterState['is_active'] === 1 ? 'active' : 'disabled'],
                ],
            ]
        );
    }

    /**
     * @param array{name: string, email: string, role: string, is_active: int} $beforeState
     * @param array{name: string, email: string, role: string, is_active: int} $afterState
     */
    public function recordUpdated(User $user, array $beforeState, array $afterState, bool $passwordChanged, ?User $actor): void
    {
        (new ActivityRecorder())->recordActorAction(
            'user.updated',
            'Updated user ' . $afterState['email'] . '.',
            $actor,
            User::class,
            (int) $user->getKey(),
            [
                'summary' => $this->userUpdateSummary($beforeState, $afterState, $passwordChanged),
                'changes' => $this->userChanges($beforeState, $afterState, $passwordChanged),
                'before' => $this->userReadableState($beforeState),
                'after' => $this->userReadableState($afterState),
            ]
        );
    }

    public function recordDeleted(User $user, ?User $actor): void
    {
        (new ActivityRecorder())->recordActorAction(
            'user.deleted',
            'Deleted user ' . (string) $user->getAttribute('email') . '.',
            $actor,
            User::class,
            (int) $user->getKey(),
            [
                'summary' => 'Soft deleted user account.',
                'state' => $this->userReadableState($this->userSnapshot($user)),
            ]
        );
    }

    public function recordRestored(User $user, ?User $actor): void
    {
        (new ActivityRecorder())->recordActorAction(
            'user.restored',
            'Restored user ' . (string) $user->getAttribute('email') . '.',
            $actor,
            User::class,
            (int) $user->getKey(),
            [
                'summary' => 'Restored user account.',
                'state' => $this->userReadableState($this->userSnapshot($user)),
            ]
        );
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
