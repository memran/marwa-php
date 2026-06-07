<?php

declare(strict_types=1);

namespace App\Modules\Settings\Models;

use App\Models\Model;

final class Setting extends Model
{
    protected static ?string $table = 'settings';

    /** @var array<int, string> */
    protected static array $fillable = [
        'category',
        'setting_key',
        'setting_value',
    ];
}
