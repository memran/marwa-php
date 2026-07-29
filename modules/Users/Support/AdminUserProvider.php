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
        $persisted = $this->persistBootstrapUser($name, $email);
        if ($persisted instanceof User) {
            return $persisted;
        }

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

    private function persistBootstrapUser(string $name, string $email): ?User
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        try {
            $existing = User::findBy('email', $email);
            if ($existing instanceof User) {
                return $existing;
            }

            if (User::query()->exists()) {
                return null;
            }

            $role = Role::findBy('slug', RolePolicy::ROLE_ADMIN);
            if (!$role instanceof Role) {
                $role = Role::create([
                    'name' => 'Admin',
                    'slug' => RolePolicy::ROLE_ADMIN,
                    'level' => RolePolicy::getRoleLevel(RolePolicy::ROLE_ADMIN),
                    'description' => 'Administrative access',
                    'is_system' => 1,
                ]);
            }

            $password = (string) env('ADMIN_BOOTSTRAP_PASSWORD', '');
            if ($password === '') {
                return null;
            }

            return User::create([
                'name' => trim($name) !== '' ? trim($name) : 'Administrator',
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => (int) $role->getKey(),
                'is_active' => true,
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function adminRoleId(): ?int
    {
        try {
            $role = Role::findBy('slug', RolePolicy::ROLE_ADMIN);
        } catch (\Throwable) {
            return null;
        }

        return $role instanceof Role ? (int) $role->getKey() : null;
    }
}
