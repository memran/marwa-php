<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\DB\ORM\Relations\BelongsToMany;
use Marwa\Framework\Database\Model;

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

    public static function findBySlug(string $slug): ?self
    {
        return self::findBy('slug', $slug);
    }

    public static function findById(int $id): ?self
    {
        return self::find($id);
    }
}
