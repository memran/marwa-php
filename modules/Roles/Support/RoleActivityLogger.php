<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use App\Modules\Users\Models\User;

final class RoleActivityLogger
{
    public function __construct(
        private readonly ActivityRecorder $activity,
        private readonly AuthManager $auth,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function roleCreated(Role $role, array $payload): void
    {
        $this->record('role.created', 'Created role.', $role, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function roleUpdated(Role $role, array $payload): void
    {
        $this->record('role.updated', 'Updated role.', $role, $payload);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function roleDeleted(int $roleId, array $state): void
    {
        $this->record('role.deleted', 'Deleted role.', null, $state, $roleId, 'role');
    }

    public function refreshPolicy(): void
    {
        RolePolicy::loadFromDatabase();
    }

    /**
     * @param array<string, mixed> $state
     */
    private function record(
        string $action,
        string $description,
        ?Role $role,
        array $state,
        ?int $subjectId = null,
        string $subjectType = 'role'
    ): void {
        $actor = $this->actor();

        $this->activity->recordActorAction(
            $action,
            $description,
            $actor,
            $subjectType,
            $subjectId ?? ($role instanceof Role ? (int) $role->getKey() : null),
            ['state' => $state]
        );
    }

    private function actor(): ?User
    {
        $user = $this->auth->user();

        return $user instanceof User ? $user : null;
    }
}
