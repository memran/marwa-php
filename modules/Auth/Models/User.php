<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\DB\ORM\Model;
use Marwa\DB\ORM\Relations\BelongsToMany;
use function db;

final class User extends Model
{
    protected static ?string $table = 'auth_users';

    protected static array $fillable = [
        'name',
        'email',
        'password',
        'status',
        'email_verified_at',
        'remember_selector',
        'remember_token_hash',
        'remember_expires_at',
        'last_login_at',
        'deleted_at',
    ];

    protected static array $casts = [
        'status' => 'bool',
    ];

    protected static bool $softDeletes = true;

    public function roles(): BelongsToMany
    {
        return new BelongsToMany(
            db(),
            'default',
            static::class,
            Role::class,
            'auth_role_user',
            'user_id',
            'role_id'
        );
    }

    public function hasRole(string|array $roles): bool
    {
        $wanted = array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            is_array($roles) ? $roles : [$roles]
        )));

        if ($wanted === []) {
            return false;
        }

        $slugs = [];

        foreach ($this->relationLoaded('roles') ? (array) $this->getRelation('roles') : [] as $role) {
            if ($role instanceof Role) {
                $slug = (string) $role->getAttribute('slug');

                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        return array_intersect($wanted, $slugs) !== [];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
