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
        return self::filterPermissions(self::query()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get());
    }

    /**
     * @return list<self>
     */
    public static function byGroup(string $group): array
    {
        return self::filterPermissions(self::query()
            ->where('group', '=', $group)
            ->orderBy('name', 'asc')
            ->get());
    }

    /**
     * @param array<int, mixed> $rows
     * @return list<self>
     */
    private static function filterPermissions(array $rows): array
    {
        return array_values(array_filter(
            $rows,
            static fn (mixed $row): bool => $row instanceof self
        ));
    }
}
