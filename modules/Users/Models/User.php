<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Models\Role;
use App\Modules\Users\Support\UserStatus;
use App\Models\Model;
use Marwa\DB\Query\Builder as BaseBuilder;
use Marwa\DB\ORM\QueryBuilder;
use Marwa\DB\ORM\Relations\BelongsTo;
use Marwa\Support\{Security, Str};

final class User extends Model implements PermissionAwareUser, AdminAuthenticatableInterface
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
        return Str::lower(trim(Security::sanitize($email, 'email')));
    }

    public static function listQuery(
        string $query = '',
        string $sort = 'created_at',
        string $direction = 'desc',
        UserStatus $status = UserStatus::All
    ): QueryBuilder {
        $user = new self();
        $builder = self::query()->with('roleRelation');
        $baseBuilder = $builder->getBaseBuilder();

        $user->scopeSearch($baseBuilder, $query);
        $user->scopeSort($baseBuilder, $sort, $direction);

        if ($status === UserStatus::Active) {
            $user->scopeActive($baseBuilder);

            return $builder;
        }

        if ($status === UserStatus::Disabled) {
            $user->scopeDisabled($baseBuilder);

            return $builder;
        }

        return match ($status) {
            UserStatus::Trashed => $builder->onlyTrashed(),
            default => $builder,
        };
    }

    public function scopeSearch(\Marwa\DB\Query\Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';

        $query->where(static function (\Marwa\DB\Query\Builder $nested) use ($like): void {
            $nested->where('name', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    public function scopeSort(\Marwa\DB\Query\Builder $query, string $sort = 'created_at', string $direction = 'desc'): void
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

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function role(): ?Role
    {
        $role = $this->getRelationValue('roleRelation');

        return $role instanceof Role ? $role : null;
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

    public function recordSuccessfulLogin(string $timestamp): void
    {
        $this->setAttribute('last_login_at', $timestamp);
        $this->save();
    }

    public function updatePasswordHash(string $hash): void
    {
        $this->setAttribute('password', $hash);
        $this->saveOrFail();
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->role();

        if ($role instanceof Role && (string) $role->getAttribute('slug') === 'admin') {
            return true;
        }

        if (!$role instanceof Role) {
            return false;
        }

        foreach ($role->permissions() as $rolePermission) {
            if ((string) $rolePermission->getAttribute('slug') === $permission) {
                return true;
            }
        }

        return false;
    }
}
