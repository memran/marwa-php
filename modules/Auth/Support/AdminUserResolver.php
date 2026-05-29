<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class AdminUserResolver
{
    public function findPersistedUserByEmail(string $email): ?User
    {
        try {
            $user = User::findBy('email', $email);
        } catch (\Throwable) {
            return null;
        }

        if (!$user instanceof User || !(bool) $user->getAttribute('is_active')) {
            return null;
        }

        return $user;
    }

    public function adminRoleId(): ?int
    {
        try {
            $role = Role::findBySlug('admin');
        } catch (\Throwable) {
            return null;
        }

        return $role instanceof Role ? (int) $role->getKey() : null;
    }
}
