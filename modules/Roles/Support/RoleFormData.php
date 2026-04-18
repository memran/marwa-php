<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Support\PermissionRepository;
use App\Modules\Auth\Support\RoleRepository;

final class RoleFormData
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,role:?Role} $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra): array
    {
        $role = $extra['role'] ?? null;
        $selectedPermissionIds = [];

        if ($role instanceof Role) {
            $selectedPermissionIds = array_map(
                static fn ($permission) => (int) $permission->getKey(),
                $this->roles->getPermissions((int) $role->getKey())
            );
        }

        $defaults = [
            'name' => $role instanceof Role ? (string) $role->getAttribute('name') : '',
            'slug' => $role instanceof Role ? (string) $role->getAttribute('slug') : '',
            'level' => $role instanceof Role ? (int) $role->getAttribute('level') : 1,
            'description' => $role instanceof Role ? (string) $role->getAttribute('description') : '',
        ];

        $old = session('_old_input', []);
        if (is_array($old)) {
            foreach (['name', 'slug', 'level', 'description'] as $field) {
                if (array_key_exists($field, $old)) {
                    $defaults[$field] = $field === 'level' ? (int) $old[$field] : (string) $old[$field];
                }
            }

            if (array_key_exists('permissions', $old) && is_array($old['permissions'])) {
                $selectedPermissionIds = array_map('intval', $old['permissions']);
            }
        }

        return array_replace([
            'errors' => session('errors', []),
            'old' => $old,
            'form' => $defaults,
            'role' => $role,
            'permissions' => $this->permissions->grouped(),
            'role_permission_ids' => $selectedPermissionIds,
        ], $extra);
    }

    /**
     * @return array{name:string,slug:string,level:int,description:string,permissions:array<int,int>}
     */
    public function payload(): array
    {
        $name = trim((string) request('name', ''));
        $slug = trim((string) request('slug', ''));
        $level = max(1, (int) request('level', 1));
        $description = trim((string) request('description', ''));
        $permissions = request('permissions', []);

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        if (!is_array($permissions)) {
            $permissions = [];
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'level' => $level,
            'description' => $description,
            'permissions' => array_values(array_unique(array_map('intval', $permissions))),
        ];
    }

    /**
     * @param array{name:string,slug:string,level:int,description:string,permissions:array<int,int>} $payload
     * @return array<string, array<int, string>>
     */
    public function validate(array $payload, bool $isSystem = false): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors['name'][] = 'The name field is required.';
        }

        if ($payload['slug'] === '') {
            $errors['slug'][] = 'The slug field is required.';
        }

        if ($payload['level'] < 1) {
            $errors['level'][] = 'The level must be at least 1.';
        }

        if ($isSystem) {
            $reserved = ['super_admin', 'admin', 'manager', 'staff', 'viewer'];
            if (!in_array($payload['slug'], $reserved, true)) {
                $errors['slug'][] = 'System roles must keep a reserved slug.';
            }
        }

        return $errors;
    }

    /**
     * @param array{name:string,slug:string,level:int,description:string,permissions:array<int,int>} $payload
     * @return array<string, mixed>
     */
    public function oldInput(array $payload): array
    {
        return [
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'level' => $payload['level'],
            'description' => $payload['description'],
            'permissions' => $payload['permissions'],
        ];
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'custom-role' : $value;
    }
}
