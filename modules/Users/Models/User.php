<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use App\Contracts\PermissionAwareUser;
use App\Modules\Auth\Contracts\AdminAuthenticatableInterface;
use App\Modules\Auth\Models\Role;
use App\Models\Model;
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
        'two_factor_secret',
        'two_factor_enabled_at',
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

    public function hasTwoFactorEnabled(): bool
    {
        $secret = trim((string) $this->getAttribute('two_factor_secret'));
        $enabledAt = trim((string) $this->getAttribute('two_factor_enabled_at'));

        return $secret !== '' && $enabledAt !== '';
    }

    public function getTwoFactorSecret(): ?string
    {
        $secret = trim((string) $this->getAttribute('two_factor_secret'));

        return $secret !== '' ? $secret : null;
    }

    public function enableTwoFactor(string $secret): void
    {
        $this->setAttribute('two_factor_secret', trim($secret));
        $this->setAttribute('two_factor_enabled_at', date('Y-m-d H:i:s'));
        $this->saveOrFail();
    }

    public function disableTwoFactor(): void
    {
        $this->setAttribute('two_factor_secret', null);
        $this->setAttribute('two_factor_enabled_at', null);
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
