<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\Framework\Database\Model;

final class PasswordResetToken extends Model
{
    protected static ?string $table = 'password_reset_tokens';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
    ];
}
