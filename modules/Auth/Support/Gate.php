<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Users\Models\User;

final class Gate
{
    private ?User $currentUser = null;
    private ?Role $currentRole = null;
    private array $cachedPermissions = [];

    public function setUser(?User $user): void
    {
        $this->currentUser = $user;
        $this->currentRole = null;
        $this->cachedPermissions = [];
    }

    public function user(): ?User
    {
        return $this->currentUser;
    }

    public function role(): ?Role
    {
        if ($this->currentRole !== null) {
            return $this->currentRole;
        }

        if ($this->currentUser === null) {
            return null;
        }

        try {
            $userRole = $this->currentUser->role();
        } catch (\Throwable) {
            $userRole = null;
        }

        if ($userRole instanceof Role) {
            $this->currentRole = $userRole;

            return $this->currentRole;
        }

        $userRoleSlug = $this->currentUser->getAttribute('role');
        if (!is_string($userRoleSlug) || trim($userRoleSlug) === '') {
            return null;
        }

        try {
            $roleRepo = app(RoleRepository::class);
            $this->currentRole = $roleRepo->findByUserRole($userRoleSlug);
        } catch (\Throwable) {
            $this->currentRole = $this->fallbackRole($userRoleSlug);

            return $this->currentRole;
        }

        if ($this->currentRole === null) {
            $this->currentRole = $this->fallbackRole($userRoleSlug);
        }

        return $this->currentRole;
    }

    public function allows(string $permission): bool
    {
        if ($this->currentUser === null) {
            return false;
        }

        $role = $this->role();
        if ($role === null) {
            return false;
        }

        if ($role->getAttribute('is_system')) {
            $roleLevel = (int) $role->getAttribute('level');
            if ($roleLevel >= 5) {
                return true;
            }
        }

        if (isset($this->cachedPermissions[$permission])) {
            return $this->cachedPermissions[$permission];
        }

        $hasPermission = $role->hasPermission($permission);
        $this->cachedPermissions[$permission] = $hasPermission;

        return $hasPermission;
    }

    public function denies(string $permission): bool
    {
        return !$this->allows($permission);
    }

    public function authorize(string $permission): bool
    {
        if ($this->denies($permission)) {
            throw new \RuntimeException("Unauthorized: Missing permission '{$permission}'");
        }
        return true;
    }

    public function hasRole(string $roleSlug): bool
    {
        $role = $this->role();

        if ($role === null) {
            return false;
        }

        return RolePolicy::hasRole((string) $role->getAttribute('slug'), $roleSlug);
    }

    public function hasAnyRole(array $roles): bool
    {
        $role = $this->role();

        if ($role === null) {
            return false;
        }

        return RolePolicy::hasAnyRole((string) $role->getAttribute('slug'), $roles);
    }

    public function isAtLevel(int $level): bool
    {
        $role = $this->role();
        if ($role === null) {
            return false;
        }

        return (int) $role->getAttribute('level') >= $level;
    }

    public function getPermissions(): array
    {
        $role = $this->role();
        if ($role === null) {
            return [];
        }

        return $role->permissions();
    }

    public function getPermissionSlugs(): array
    {
        return array_map(
            static fn ($p) => $p->getAttribute('slug'),
            $this->getPermissions()
        );
    }

    public function can(string $ability, $model = null): bool
    {
        if ($this->currentUser === null) {
            return false;
        }

        $role = $this->role();
        if ($role === null) {
            return false;
        }

        if ($role->getAttribute('is_system') && (int) $role->getAttribute('level') >= 5) {
            return true;
        }

        $permission = $ability;
        if ($model !== null && is_object($model)) {
            $resource = method_exists($model, 'getTable') ? $model->getTable() : 'unknown';
            $permission = "{$resource}.{$ability}";
        }

        return $this->allows($permission);
    }

    private function fallbackRole(string $slug): ?Role
    {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return null;
        }

        $level = match ($slug) {
            'super_admin', 'admin' => 5,
            'manager' => 4,
            'staff' => 2,
            'viewer' => 1,
            default => 0,
        };

        return Role::newInstance([
            'id' => 0,
            'name' => RolePolicy::getRoleName($slug),
            'slug' => $slug,
            'level' => $level,
            'description' => 'Bootstrap fallback role',
            'is_system' => true,
        ], false);
    }
}
