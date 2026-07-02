<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Models\Model;
use Marwa\DB\ORM\Relations\BelongsToMany;
use Marwa\DB\ORM\Relations\HasMany;

final class Role extends Model
{
    protected static ?string $table = 'roles';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'slug',
        'level',
        'description',
        'is_system',
    ];

    protected static array $casts = [
        'level' => 'int',
        'is_system' => 'bool',
    ];

    public static function nameForId(?int $roleId, bool $unknown = true): string
    {
        if ($roleId === null) {
            return $unknown ? 'Unknown' : '';
        }

        $role = self::find($roleId);

        if ($role === null) {
            return $unknown ? 'Unknown' : '';
        }

        return (string) $role->getAttribute('name');
    }

    public function permissionsRelation(): BelongsToMany
    {
        if (static::$cm === null) {
            throw new \RuntimeException('ConnectionManager not set. Call Model::setConnectionManager().');
        }

        return new BelongsToMany(
            static::$cm,
            static::$connection,
            static::class,
            Permission::class,
            'role_permission',
            'role_id',
            'permission_id'
        );
    }

    public function usersRelation(): HasMany
    {
        return $this->hasMany(\App\Modules\Users\Models\User::class, 'role_id');
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        if (static::$cm === null) {
            return [];
        }

        if ($this->relationLoaded('permissionsRelation')) {
            $permissions = $this->getRelation('permissionsRelation');

            return is_array($permissions) ? $permissions : [];
        }

        if ($this->getKey() === null) {
            return [];
        }

        $this->permissionsRelation()->eagerLoad([$this], 'permissionsRelation');
        $permissions = $this->getRelation('permissionsRelation');

        return is_array($permissions) ? $permissions : [];
    }

    public function hasPermission(string $permissionSlug): bool
    {
        foreach ($this->permissions() as $permission) {
            if ($permission->getAttribute('slug') === $permissionSlug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<int|string> $permissionIds
     */
    public function syncPermissionIds(array $permissionIds): void
    {
        if (static::$cm === null || $this->getKey() === null) {
            return;
        }

        $normalized = array_values(array_unique(array_map(
            static fn (int|string $permissionId): int => (int) $permissionId,
            $permissionIds
        )));

        $this->permissionsRelation()->sync($this, $normalized);
    }

    public function attachPermissionId(int $permissionId): void
    {
        if (static::$cm === null || $this->getKey() === null || $permissionId <= 0) {
            return;
        }

        foreach ($this->permissionsRelation()->get($this) as $permission) {
            if ((int) $permission->getKey() === $permissionId) {
                return;
            }
        }

        $this->permissionsRelation()->attach($this, $permissionId);
    }
}
