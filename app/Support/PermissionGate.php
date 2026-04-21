<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Support\AuthManager;

final class PermissionGate
{
    /**
     * @var null|callable(): (PermissionAwareUser|null)
     */
    private $currentUserResolver = null;

    public function policy(string $modelClass): void
    {
        // Policy registration is a compatibility no-op for the starter.
    }

    public function withCurrentUserResolver(callable $resolver): self
    {
        $gate = clone $this;
        $gate->currentUserResolver = $resolver;

        return $gate;
    }

    public function allows(string $ability): bool
    {
        $user = $this->currentUser();

        if ($user === null) {
            return false;
        }

        return $user->hasPermission($ability);
    }

    public function denies(string $ability): bool
    {
        return !$this->allows($ability);
    }

    private function currentUser(): ?PermissionAwareUser
    {
        if ($this->currentUserResolver !== null) {
            return ($this->currentUserResolver)();
        }

        if (!isset($GLOBALS['marwa_app'])) {
            return null;
        }

        return app(AuthManager::class)->user();
    }
}
