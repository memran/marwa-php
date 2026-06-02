<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Marwa\Framework\Database\Model;
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

    public static function findBy(string $column, mixed $value): ?static
    {
        if ($value === null) {
            return null;
        }

        $row = static::applySoftDeleteFilter(static::baseQuery())
            ->where($column, '=', $value)
            ->first();

        if ($row === null) {
            return null;
        }

        $data = is_array($row) ? $row : (array) $row;

        return new self($data, true);
    }

    public static function findByEmailIncludingTrashed(string $email): ?static
    {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $row = self::baseQuery()->where('email', '=', $email)->first();

        if ($row === null) {
            return null;
        }

        $data = is_array($row) ? $row : (array) $row;

        return new self($data, true);
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
