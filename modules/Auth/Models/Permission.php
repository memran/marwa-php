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
        return self::findBy('slug', $slug);
    }

    public static function findById(int $id): ?self
    {
        return self::find($id);
    }

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
