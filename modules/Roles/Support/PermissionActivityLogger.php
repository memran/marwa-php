<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\Auth\Models\Permission;

final class PermissionActivityLogger
{
    /**
     * @param array<string, mixed> $payload
     */
    public function permissionCreated(Permission $permission, array $payload): void
    {
        $this->record('permission.created', 'Created permission.', $permission, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function permissionUpdated(Permission $permission, array $payload): void
    {
        $this->record('permission.updated', 'Updated permission.', $permission, $payload);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function permissionDeleted(int $permissionId, array $state = []): void
    {
        $this->record('permission.deleted', 'Deleted permission.', null, $state, $permissionId, 'permission');
    }

    /**
     * @param array<string, mixed> $state
     */
    private function record(
        string $action,
        string $description,
        ?Permission $permission,
        array $state,
        ?int $subjectId = null,
        string $subjectType = 'permission'
    ): void {
        event(new ActivityRecordingRequested(
            $action,
            $description,
            $subjectType,
            $subjectId ?? ($permission instanceof Permission ? (int) $permission->getKey() : null),
            ['state' => $state]
        ));
    }
}
