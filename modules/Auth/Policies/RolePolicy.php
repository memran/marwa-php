<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\AuthManager;

final class RolePolicy
{
    public function __construct(
        private AuthManager $auth
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->auth->isAdmin($user);
    }

    public function manage(User $user): bool
    {
        return $this->auth->isAdmin($user);
    }
}
