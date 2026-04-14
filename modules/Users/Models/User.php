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
        'role_id',
        'is_active',
        'last_login_at',
    ];

    protected static array $casts = [
        'is_active' => 'int',
        'role_id' => 'int',
    ];

    protected static bool $softDeletes = true;

    public function role(): ?\App\Modules\Auth\Models\Role
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return null;
        }

        $roleId = $this->getAttribute('role_id');
        if ($roleId === null) {
            return null;
        }

        return \App\Modules\Auth\Models\Role::findById((int) $roleId);
    }
}
