<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\AuthManager;

final class UserPolicy
{
    public function __construct(
        private AuthManager $auth
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->auth->isAdmin($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->auth->isAdmin($user) || $user->getKey() === $target->getKey();
    }

    public function update(User $user, User $target): bool
    {
        return $this->view($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->auth->isAdmin($user) && $user->getKey() !== $target->getKey();
    }

    public function changePassword(User $user, User $target): bool
    {
        return $this->view($user, $target);
    }
}
