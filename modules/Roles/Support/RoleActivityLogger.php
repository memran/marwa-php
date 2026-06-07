<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\RolePolicy;

final class RoleActivityLogger
{
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
        event(new ActivityRecordingRequested(
            $action,
            $description,
            $subjectType,
            $subjectId ?? ($role instanceof Role ? (int) $role->getKey() : null),
            ['state' => $state]
        ));
    }
}
