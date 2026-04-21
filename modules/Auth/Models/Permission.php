<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\Framework\Database\Model;

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

    public static function findBySlug(string $slug): ?self
    {
        $row = self::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug)
            ->first();

        return $row === null ? null : self::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public static function findById(int $id): ?self
    {
        $row = self::newQuery()->getBaseBuilder()
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : self::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        $rows = self::newQuery()->getBaseBuilder()
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array|object $row): self => self::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    /**
     * @return list<self>
     */
    public static function byGroup(string $group): array
    {
        $rows = self::newQuery()->getBaseBuilder()
            ->where('group', '=', $group)
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array|object $row): self => self::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }
}
