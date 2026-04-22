<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Marwa\Framework\Database\Model;

final class User extends Model implements PermissionAwareUser
{
    protected static ?string $table = 'users';

    private ?\App\Modules\Auth\Models\Role $roleCache = null;
    private ?int $roleCacheId = null;

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'last_login_at',
    ];

    protected static array $casts = [
        'is_active' => 'int',
        'role_id' => 'int',
    ];

    protected static bool $softDeletes = true;

    public static function findByEmail(string $email): ?self
    {
        return self::findBy('email', $email);
    }

    public static function findById(int $id): ?self
    {
        return self::find($id);
    }

    public function getId(): ?int
    {
        $key = $this->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    public function getEmail(): ?string
    {
        $email = trim((string) $this->getAttribute('email'));

        return $email !== '' ? $email : null;
    }

    public function getPasswordHash(): ?string
    {
        $password = trim((string) $this->getAttribute('password'));

        return $password !== '' ? $password : null;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $role = $this->role();

        return $role === null ? [] : [(string) $role->getAttribute('slug')];
    }

    /**
     * @return list<string>
     */
    public function getPermissions(): array
    {
        $role = $this->role();

        if ($role === null) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn (Permission $permission): string => (string) $permission->getAttribute('slug'),
                $role->permissions()
            )
        ));
    }

    public function role(): ?Role
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return null;
        }

        $roleId = $this->getAttribute('role_id');
        if ($roleId === null) {
            return null;
        }

        $roleId = (int) $roleId;

        if ($this->roleCacheId === $roleId) {
            return $this->roleCache;
        }

        $this->roleCacheId = $roleId;
        $this->roleCache = Role::findById($roleId);

        return $this->roleCache;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return in_array($permission, $this->getPermissions(), true);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }
}
