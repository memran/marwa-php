<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Users\Models\User;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;

class UsersPolicy
{
    /**
     * Determine if the user can view the given user.
     */
    public function view(?User $user): bool
    {
        return $this->isAuthenticatedAdmin();
    }

    /**
     * Determine if the user can create users.
     */
    public function create(): bool
    {
        return $this->isAuthenticatedAdmin();
    }

    /**
     * Determine if the user can update the given user.
     */
    public function update(?User $user): bool
    {
        return $this->isAuthenticatedAdmin();
    }

    /**
     * Determine if the user can delete the given user.
     */
    public function delete(?User $user): bool
    {
        return $this->isAuthenticatedAdmin();
    }

    /**
     * Determine if the user can restore the given user.
     */
    public function restore(?User $user): bool
    {
        return $this->isAuthenticatedAdmin();
    }

    /**
     * Check if the current user is an authenticated admin (has admin role and is authenticated).
     */
    protected function isAuthenticatedAdmin(): bool
    {
        $auth = app(AuthManager::class);
        $currentUser = $auth->user();

        if ($currentUser === null) {
            return false;
        }

        $authUser = $auth->user();
        $role = $authUser->role();

        if ($role === null) {
            return false;
        }

        return RolePolicy::hasRole($role->getAttribute('slug'), RolePolicy::ROLE_ADMIN);
    }
}