<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Models\Model;

final class Permission extends Model
{
    protected static ?string $table = 'permissions';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'name',
        'slug',
        'description',
        'group',
    ];

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::query()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * @return list<self>
     */
    public static function byGroup(string $group): array
    {
        return self::query()
            ->where('group', '=', $group)
            ->orderBy('name', 'asc')
            ->get();
    }
}
