<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Marwa\DB\Connection\ConnectionManager;

final class RoleRepository
{
    private ConnectionManager $cm;

    public function __construct()
    {
        $this->cm = app(ConnectionManager::class);
    }

    public function all(): array
    {
        $rows = Role::newQuery()->getBaseBuilder()
            ->orderBy('level', 'desc')
            ->get();

        return array_map(
            static fn (array|object $row): Role => Role::newInstance(
                is_array($row) ? $row : (array) $row,
                true
            ),
            $rows
        );
    }

    public function findById(int $id): ?Role
    {
        $row = Role::newQuery()->getBaseBuilder()
            ->where('id', '=', $id)
            ->first();

        return $row === null ? null : Role::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function findBySlug(string $slug): ?Role
    {
        $row = Role::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug)
            ->first();

        return $row === null ? null : Role::newInstance(is_array($row) ? $row : (array) $row, true);
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $role = $this->findById($id);
        if ($role === null) {
            return false;
        }

        $role->fill($data);

        return $role->save();
    }

    public function delete(int $id): bool
    {
        $role = $this->findById($id);
        if ($role === null) {
            return false;
        }

        if ($role->getAttribute('is_system')) {
            return false;
        }

        return $role->delete();
    }

    public function countUsers(int $roleId): int
    {
        $pdo = $this->cm->getPdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL');
        $stmt->execute([$roleId]);

        return (int) $stmt->fetchColumn();
    }

    public function hasSlug(string $slug, ?int $ignoreId = null): bool
    {
        $builder = Role::newQuery()->getBaseBuilder()
            ->where('slug', '=', $slug);

        if ($ignoreId !== null) {
            $builder->where('id', '!=', $ignoreId);
        }

        return $builder->count() > 0;
    }

    public function getPermissions(int $roleId): array
    {
        $pdo = $this->cm->getPdo();
        $stmt = $pdo->prepare(
            'SELECT p.* FROM permissions p 
             INNER JOIN role_permission rp ON p.id = rp.permission_id 
             WHERE rp.role_id = ? 
             ORDER BY p."group", p.name'
        );
        $stmt->execute([$roleId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(
            static fn (array $row): Permission => Permission::newInstance($row, true),
            $rows
        );
    }

    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        $pdo = $this->cm->getPdo();

        $pdo->prepare('DELETE FROM role_permission WHERE role_id = ?')->execute([$roleId]);

        if (empty($permissionIds)) {
            return true;
        }

        $stmt = $pdo->prepare('INSERT INTO role_permission (role_id, permission_id) VALUES (?, ?)');
        foreach ($permissionIds as $permissionId) {
            $stmt->execute([$roleId, $permissionId]);
        }

        return true;
    }

    /**
     * @return array<int, array{value:int,label:string}>
     */
    public function levelOptions(): array
    {
        return [
            ['value' => 1, 'label' => 'Viewer'],
            ['value' => 2, 'label' => 'Staff'],
            ['value' => 3, 'label' => 'Custom'],
            ['value' => 4, 'label' => 'Manager'],
            ['value' => 5, 'label' => 'Admin'],
        ];
    }

    public function findByUserRole(string $userRole): ?Role
    {
        $roleSlug = strtolower(trim($userRole));

        $role = $this->findBySlug($roleSlug);
        if ($role !== null) {
            return $role;
        }

        $fallbackRoles = [
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'user' => 'admin',
            'manager' => 'manager',
            'staff' => 'staff',
            'viewer' => 'viewer',
        ];

        if (isset($fallbackRoles[$roleSlug])) {
            return $this->findBySlug($fallbackRoles[$roleSlug]);
        }

        return $this->findBySlug('staff');
    }
}
