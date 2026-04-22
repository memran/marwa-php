<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Role;

final class RolePolicy
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_USER,
    ];

    /**
     * @var array<string, int>
     */
    private static array $defaultHierarchy = [
        self::ROLE_ADMIN => 10,
        self::ROLE_USER => 1,
    ];

    /**
     * @var array<string, int>|null
     */
    private static ?array $roleLevels = null;

    /**
     * @var int|null
     */
    private static ?int $highestRoleLevel = null;

    public static function hasRole(?string $userRole, string $requiredRole): bool
    {
        if ($userRole === null) {
            return false;
        }

        $userRole = strtolower(trim($userRole));
        $requiredRole = strtolower(trim($requiredRole));

        if ($requiredRole === '') {
            return false;
        }

        $userLevel = self::getRoleLevel($userRole);
        $requiredLevel = self::getRoleLevel($requiredRole);

        if ($userLevel <= 0 || $requiredLevel <= 0) {
            return false;
        }

        return $userLevel >= $requiredLevel;
    }

    public static function isAdmin(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_ADMIN);
    }

    public static function isUser(?string $userRole): bool
    {
        return $userRole === self::ROLE_USER;
    }

    /**
     * @param list<string> $roles
     */
    public static function hasAnyRole(?string $userRole, array $roles): bool
    {
        if ($userRole === self::ROLE_ADMIN) {
            return true;
        }

        foreach ($roles as $role) {
            if ($userRole === $role) {
                return true;
            }
        }
        return false;
    }

    public static function isValidRole(string $role): bool
    {
        return in_array(strtolower(trim($role)), self::ROLES, true);
    }

    public static function getRoleLevel(?string $role): int
    {
        $role = strtolower(trim($role ?? ''));

        if (self::$roleLevels !== null && isset(self::$roleLevels[$role])) {
            return self::$roleLevels[$role];
        }

        return self::$roleLevels[$role] ?? self::$defaultHierarchy[$role] ?? 0;
    }

    public static function isSuperAdmin(?string $userRole): bool
    {
        if ($userRole === null) {
            return false;
        }

        $userLevel = self::getRoleLevel($userRole);

        return $userLevel > 0 && $userLevel >= self::getHighestRoleLevel();
    }

    /**
     * @param array<string, int>|null $levels
     */
    public static function setRoleLevels(?array $levels): void
    {
        self::$roleLevels = $levels;
        self::$highestRoleLevel = null;
    }

    public static function canAccess(string $userRole, string $requiredRole): bool
    {
        return self::hasRole($userRole, $requiredRole);
    }

    public static function getRoleName(string $slug): string
    {
        $names = [
            'admin' => 'Admin',
            'user' => 'User',
        ];

        return $names[strtolower($slug)] ?? ucfirst($slug);
    }

    public static function loadFromDatabase(): void
    {
        if (!function_exists('app')) {
            return;
        }

        try {
            $rows = Role::newQuery()->getBaseBuilder()
                ->select('slug', 'level')
                ->where('level', '>', 0)
                ->get();

            $levels = [];
            foreach ($rows as $row) {
                $levels[(string) $row['slug']] = (int) $row['level'];
            }

            self::$roleLevels = $levels;
            self::$highestRoleLevel = $levels === [] ? null : max($levels);
        } catch (\Throwable) {
        }
    }

    public static function resetToDefaults(): void
    {
        self::$roleLevels = null;
        self::$highestRoleLevel = null;
    }

    private static function getHighestRoleLevel(): int
    {
        if (self::$highestRoleLevel !== null) {
            return self::$highestRoleLevel;
        }

        if (self::$roleLevels !== null && self::$roleLevels !== []) {
            self::$highestRoleLevel = max(self::$roleLevels);

            return self::$highestRoleLevel;
        }

        self::$highestRoleLevel = max(self::$defaultHierarchy);

        return self::$highestRoleLevel;
    }
}
