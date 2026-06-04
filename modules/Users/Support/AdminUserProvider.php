<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Contracts\AdminUserProviderInterface;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\RolePolicy;
use App\Modules\Users\Models\User;

final class AdminUserProvider implements AdminUserProviderInterface
{
    public function findPersistedUserByEmail(string $email): ?AdminAuthenticatableInterface
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

    public function findPersistedUserById(int $id): ?AdminAuthenticatableInterface
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $user = User::find($id);
        } catch (\Throwable) {
            return null;
        }

        if (!$user instanceof User || !(bool) $user->getAttribute('is_active')) {
            return null;
        }

        return $user;
    }

    public function createBootstrapUser(string $name, string $email): AdminAuthenticatableInterface
    {
        $user = User::newInstance([
            'id' => 0,
            'name' => $name,
            'email' => $email,
            'role_id' => $this->adminRoleId(),
            'is_active' => true,
        ], false);

        $user->setRelation('roleRelation', Role::newInstance([
            'id' => $this->adminRoleId(),
            'name' => 'Administrator',
            'slug' => RolePolicy::ROLE_ADMIN,
            'level' => RolePolicy::getRoleLevel(RolePolicy::ROLE_ADMIN),
            'is_system' => true,
        ], false));

        return $user;
    }

    private function adminRoleId(): ?int
    {
        try {
            $role = Role::findBySlug(RolePolicy::ROLE_ADMIN);
        } catch (\Throwable) {
            return null;
        }

        return $role instanceof Role ? (int) $role->getKey() : null;
    }
}
