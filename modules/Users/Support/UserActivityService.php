<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Activity\Support\ActivityRecorder;
use App\Modules\Users\Models\User;

final class UserActivityService
{
    public function __construct(
        private readonly UserActivityPayloads $payloads,
        private readonly UserActivityState $state,
        private readonly ?ActivityRecorder $recorder = null,
    ) {}

    /**
     * @return array{name: string, email: string, role: string, is_active: int}
     */
    public function userSnapshot(User $user): array
    {
        return $this->state->userSnapshot($user);
    }

    /**
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $before
     * @param array{name: string, email: string, role_id: int|null, role_name: string, is_active: int} $after
     */
    public function userStateHasChanges(array $before, array $after): bool
    {
        return $this->state->userStateHasChanges($before, $after);
    }

    public function recordCreated(User $user, array $afterState, ?User $actor): void
    {
        $this->dispatch($this->payloads->createdPayload($user, $afterState), $actor);
    }

    public function recordUpdated(
        User $user,
        array $beforeState,
        array $afterState,
        bool $passwordChanged,
        ?User $actor
    ): void {
        $this->dispatch(
            $this->payloads->updatedPayload($user, $beforeState, $afterState, $passwordChanged),
            $actor
        );
    }

    public function recordStatusChanged(User $user, array $beforeState, array $afterState, ?User $actor): void
    {
        $this->dispatch($this->payloads->statusChangedPayload($user, $beforeState, $afterState), $actor);
    }

    public function recordDeleted(User $user, ?User $actor): void
    {
        $this->dispatch($this->payloads->deletedPayload($user), $actor);
    }

    public function recordRestored(User $user, ?User $actor): void
    {
        $this->dispatch($this->payloads->restoredPayload($user), $actor);
    }

    /**
     * @param array{action:string,description:string,subjectType:string,subjectId:int,details:array<string,mixed>} $payload
     */
    private function dispatch(array $payload, ?User $actor): void
    {
        if ($this->recorder === null) {
            return;
        }

        $this->recorder->recordActorAction(
            $payload['action'],
            $payload['description'],
            $actor,
            $payload['subjectType'],
            $payload['subjectId'],
            $payload['details']
        );
    }
}
