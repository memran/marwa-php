<?php

declare(strict_types=1);

namespace App\Modules\Users\Models;

use Marwa\Framework\Database\Model;

final class User extends Model
{
    protected static ?string $table = 'users';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $casts = [
        'is_active' => 'int',
    ];

    protected static bool $softDeletes = true;
}
