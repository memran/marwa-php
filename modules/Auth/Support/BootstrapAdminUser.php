<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Models\Role;

final class BootstrapAdminUser implements AdminAuthenticatableInterface
{
    public function __construct(
        private readonly string $name,
        private readonly string $email,
    ) {
    }

    public function hasPermission(string $permission): bool
    {
        return true;
    }

    public function getAttribute(string $key): mixed
    {
        return match ($key) {
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => true,
            default => null,
        };
    }

    public function getId(): ?int
    {
        return null;
    }

    public function role(): ?Role
    {
        return Role::newInstance([
            'id' => 0,
            'name' => 'Administrator',
            'slug' => RolePolicy::ROLE_ADMIN,
            'level' => RolePolicy::getRoleLevel(RolePolicy::ROLE_ADMIN),
            'is_system' => true,
        ], false);
    }

    public function getPasswordHash(): ?string
    {
        return null;
    }

    public function recordSuccessfulLogin(string $timestamp): void
    {
    }

    public function updatePasswordHash(string $hash): void
    {
    }
}
