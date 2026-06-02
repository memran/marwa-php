<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class UserActivityPayloads
{
    public function __construct(
        private readonly UserActivityState $state,
    ) {}

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    public function createdPayload(User $user, array $afterState): array
    {
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
                    'role' => ['before' => null, 'after' => Role::nameForId($afterState['role_id'])],
                    'status' => ['before' => null, 'after' => $afterState['is_active'] === 1 ? UserStatus::Active->value : UserStatus::Disabled->value],
                ],
            ],
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    public function updatedPayload(User $user, array $beforeState, array $afterState, bool $passwordChanged): array
    {
        $before = $this->normalizeState($beforeState);
        $after = $this->normalizeState($afterState);

        return [
            'action' => 'user.updated',
            'description' => 'Updated user ' . $afterState['email'] . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => $this->updateDetails($before, $after, $passwordChanged),
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $state
     * @return array{name:string,email:string,role_id:int|null,role_name:string,is_active:int}
     */
    private function normalizeState(array $state): array
    {
        $roleId = $state['role_id'] ?? null;

        return [
            'name' => $state['name'],
            'email' => $state['email'],
            'role_id' => $roleId,
            'role_name' => Role::nameForId($roleId, false),
            'is_active' => $state['is_active'],
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int|null,role_name:string,is_active:int} $before
     * @param array{name:string,email:string,role_id:int|null,role_name:string,is_active:int} $after
     * @return array<string, mixed>
     */
    private function updateDetails(array $before, array $after, bool $passwordChanged): array
    {
        return [
            'summary' => $this->state->userUpdateSummary($before, $after, $passwordChanged),
            'changes' => $this->state->userChanges($before, $after, $passwordChanged),
            'before' => $this->state->userReadableState($before),
            'after' => $this->state->userReadableState($after),
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int} $beforeState
     * @param array{name:string,email:string,role_id:int,is_active:int} $afterState
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    public function statusChangedPayload(User $user, array $beforeState, array $afterState): array
    {
        $isActive = $afterState['is_active'] === 1;
        $verb = $isActive ? 'Enabled' : 'Disabled';
        $beforeStatus = $this->statusLabel($beforeState['is_active']);
        $afterStatus = $this->statusLabel($afterState['is_active']);

        return [
            'action' => $isActive ? 'user.enabled' : 'user.disabled',
            'description' => $verb . ' user ' . $afterState['email'] . '.',
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => [
                'summary' => $verb . ' user account.',
                'changes' => ['Status' => ['before' => $beforeStatus, 'after' => $afterStatus]],
                'before' => ['Status' => $beforeStatus],
                'after' => ['Status' => $afterStatus],
            ],
        ];
    }

    private function statusLabel(int $isActive): string
    {
        return $isActive === 1 ? 'Active' : 'Disabled';
    }

    /**
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    public function deletedPayload(User $user): array
    {
        return $this->payload($user, 'user.deleted', 'Deleted user ' . (string) $user->getAttribute('email') . '.', [
            'summary' => 'Soft deleted user account.',
            'state' => $this->state->userReadableState($this->state->userSnapshot($user)),
        ]);
    }

    /**
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    public function restoredPayload(User $user): array
    {
        return $this->payload($user, 'user.restored', 'Restored user ' . (string) $user->getAttribute('email') . '.', [
            'summary' => 'Restored user account.',
            'state' => $this->state->userReadableState($this->state->userSnapshot($user)),
        ]);
    }

    /**
     * @param array<string, mixed> $details
     * @return array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>}
     */
    private function payload(User $user, string $action, string $description, array $details): array
    {
        return [
            'action' => $action,
            'description' => $description,
            'subjectType' => User::class,
            'subjectId' => (int) $user->getKey(),
            'details' => $details,
        ];
    }
}
