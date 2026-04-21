<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Role;

final class RolePolicy
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_STAFF,
        self::ROLE_VIEWER,
    ];

    /**
     * @var array<string, int>
     */
    private static array $defaultHierarchy = [
        self::ROLE_SUPER_ADMIN => 5,
        self::ROLE_ADMIN => 5,
        self::ROLE_MANAGER => 4,
        self::ROLE_STAFF => 2,
        self::ROLE_VIEWER => 1,
    ];

    /**
     * @var array<string, int>|null
     */
    private static ?array $roleLevels = null;

    public static function hasRole(?string $userRole, string $requiredRole): bool
    {
        if ($userRole === null) {
            return false;
        }

        $userRole = strtolower(trim($userRole));
        $requiredRole = strtolower(trim($requiredRole));

        $userLevel = self::getRoleLevel($userRole);
        $requiredLevel = self::getRoleLevel($requiredRole);

        return $userLevel >= $requiredLevel;
    }

    public static function isSuperAdmin(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_SUPER_ADMIN);
    }

    public static function isAdmin(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_ADMIN);
    }

    public static function isManager(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_MANAGER);
    }

    public static function isStaff(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_STAFF);
    }

    public static function isViewer(?string $userRole): bool
    {
        return self::hasRole($userRole, self::ROLE_VIEWER);
    }

    /**
     * @param list<string> $roles
     */
    public static function hasAnyRole(?string $userRole, array $roles): bool
    {
        foreach ($roles as $role) {
            if (self::hasRole($userRole, $role)) {
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

    /**
     * @param array<string, int>|null $levels
     */
    public static function setRoleLevels(?array $levels): void
    {
        self::$roleLevels = $levels;
    }

    public static function canAccess(string $userRole, string $requiredRole): bool
    {
        return self::hasRole($userRole, $requiredRole);
    }

    public static function getRoleName(string $slug): string
    {
        $names = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
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
        } catch (\Throwable) {
        }
    }

    public static function resetToDefaults(): void
    {
        self::$roleLevels = null;
    }
}
