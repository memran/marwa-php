<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\DB\ORM\Model;
use Marwa\DB\ORM\Relations\BelongsToMany;
use function db;

final class Role extends Model
{
    protected static ?string $table = 'auth_roles';

    protected static array $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected static array $casts = [
        'is_default' => 'bool',
    ];

    public function users(): BelongsToMany
    {
        return new BelongsToMany(
            db(),
            'default',
            static::class,
            User::class,
            'auth_role_user',
            'role_id',
            'user_id'
        );
    }
}
