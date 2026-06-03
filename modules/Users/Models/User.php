<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Marwa\Framework\Database\Model;
use Marwa\DB\Query\Builder as BaseBuilder;
use Marwa\DB\ORM\Relations\BelongsTo;

final class User extends Model implements PermissionAwareUser
{
    protected static ?string $table = 'users';

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

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function scopeSearch(BaseBuilder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';

        $query->where(static function (BaseBuilder $nested) use ($like): void {
            $nested->where('name', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function scopeSort(BaseBuilder $query, string $sort = 'created_at', string $direction = 'desc'): void
    {
        $column = match (trim($sort)) {
            'name' => 'name',
            'email' => 'email',
            'role' => 'role_id',
            'role_id' => 'role_id',
            'is_active' => 'is_active',
            'updated_at' => 'updated_at',
            default => 'created_at',
        };

        $query->orderBy($column, strtolower(trim($direction)) === 'asc' ? 'asc' : 'desc');
    }

    public function scopeActive(BaseBuilder $query): void
    {
        $query->where('is_active', '=', 1);
    }

    public function scopeDisabled(BaseBuilder $query): void
    {
        $query->where('is_active', '=', 0);
    }

    public static function findBy(string $column, mixed $value): ?static
    {
        if ($value === null) {
            return null;
        }

        $row = static::query()
            ->where($column, '=', $value)
            ->first();

        return $row instanceof static ? $row : null;
    }

    public static function findByEmailIncludingTrashed(string $email): ?static
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $row = static::withTrashed()
            ->where('email', '=', $email)
            ->first();

        return $row instanceof static ? $row : null;
    }

    public function roleRelation(): BelongsTo
    {
        if (static::$cm === null) {
            throw new \RuntimeException('ConnectionManager not set. Call Model::setConnectionManager().');
        }

        return new BelongsTo(
            static::$cm,
            static::$connection,
            static::class,
            Role::class,
            'role_id'
        );
    }

    public function getId(): ?int
    {
        $key = $this->getKey();

        return is_numeric($key) ? (int) $key : null;
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
        if (static::$cm === null) {
            return null;
        }

        $roleId = $this->getAttribute('role_id');
        if ($roleId === null) {
            return null;
        }

        if ($this->relationLoaded('roleRelation')) {
            $role = $this->getRelation('roleRelation');

            return $role instanceof Role ? $role : null;
        }

        $this->roleRelation()->eagerLoad([$this], 'roleRelation');
        $role = $this->getRelation('roleRelation');

        return $role instanceof Role ? $role : null;
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
