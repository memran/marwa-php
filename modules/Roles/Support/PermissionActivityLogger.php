<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;

final class PermissionActivityLogger
{
    public function __construct(
        private readonly ActivityRecorder $activity,
        private readonly AuthManager $auth,
    ) {
    }

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
        $actor = $this->actor();

        $this->activity->recordActorAction(
            $action,
            $description,
            $actor,
            $subjectType,
            $subjectId ?? ($permission instanceof Permission ? (int) $permission->getKey() : null),
            ['state' => $state]
        );
    }

    private function actor(): ?User
    {
        $user = $this->auth->user();

        return $user instanceof User ? $user : null;
    }
}
