<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Users\Models\User;
use App\Modules\Auth\Support\RolePolicy;

class UsersPolicy
{
    /**
     * Determine if the user can view the given user.
     */
    public function view(User $actor, mixed $user = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Determine if the user can view the user list.
     */
    public function viewAny(User $actor, mixed $resource = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $actor, mixed $resource = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Determine if the user can update the given user.
     */
    public function update(User $actor, ?User $user = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Determine if the user can delete the given user.
     */
    public function delete(User $actor, ?User $user = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Determine if the user can restore the given user.
     */
    public function restore(User $actor, ?User $user = null): bool
    {
        return $this->isAuthenticatedAdmin($actor);
    }

    /**
     * Check if the current user is an authenticated admin (has admin role and is authenticated).
     */
    protected function isAuthenticatedAdmin(User $actor): bool
    {
        $role = $actor->role();

        if ($role === null) {
            return false;
        }

        return RolePolicy::hasRole($role->getAttribute('slug'), RolePolicy::ROLE_ADMIN);
    }
}
