<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\DB\ORM\Model;

final class PasswordReset extends Model
{
    protected static ?string $table = 'auth_password_resets';

    protected static array $fillable = [
        'email',
        'token_hash',
        'expires_at',
        'used_at',
    ];
}
