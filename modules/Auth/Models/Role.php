<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use Marwa\DB\Facades\DB;
use Marwa\Framework\Database\Model;

final class Role extends Model
{
    protected static ?string $table = 'roles';

    protected static array $fillable = [
        'name',
        'slug',
        'level',
        'description',
        'is_system',
    ];

    protected static array $casts = [
        'level' => 'int',
        'is_system' => 'bool',
    ];

    public function permissions(): array
    {
        if (!app()->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return [];
        }

        $permissionIds = DB::table('role_permission')
            ->where('role_id', '=', (int) $this->getKey())
            ->pluck('permission_id')
            ->toArray();

        if ($permissionIds === []) {
            return [];
        }

        $rows = Permission::newQuery()->getBaseBuilder()
            ->whereIn('id', array_map('intval', $permissionIds))
            ->orderBy('group', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return array_map(
            static fn (array $row): \App\Modules\Auth\Models\Permission => 
                \App\Modules\Auth\Models\Permission::newInstance($row, true),
            $rows
        );
    }

    public function hasPermission(string $permissionSlug): bool
    {
        $permissions = $this->permissions();
        foreach ($permissions as $permission) {
            if ($permission->getAttribute('slug') === $permissionSlug) {
                return true;
            }
        }
        return false;
    }

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
}
