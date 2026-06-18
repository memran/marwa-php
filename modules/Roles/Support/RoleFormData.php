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
        private readonly Slugger $slugger,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,back_url?:string,role:?Role} $extra
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
                if (!array_key_exists($field, $old)) {
                    continue;
                }

                $defaults[$field] = $field === 'level' ? (int) $old[$field] : (string) $old[$field];
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
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'trim|required|string|max:120',
            'slug' => 'trim|string|max:120',
            'level' => 'integer|min:1',
            'description' => 'trim|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name must not exceed 120 characters.',
            'level.min' => 'The level must be at least 1.',
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @param mixed $permissions
     * @return array{name:string,slug:string,level:int,description:string,permissions:array<int,int>}
     */
    public function normalize(array $validated, mixed $permissions = []): array
    {
        $name = trim((string) ($validated['name'] ?? ''));
        $slug = trim((string) ($validated['slug'] ?? ''));

        if ($slug === '') {
            $slug = $this->slugger->slugify($name, 'custom-role');
        }

        if (!is_array($permissions)) {
            $permissions = [];
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'level' => max(1, (int) ($validated['level'] ?? 1)),
            'description' => trim((string) ($validated['description'] ?? '')),
            'permissions' => array_values(array_unique(array_map('intval', $permissions))),
        ];
    }

    /**
     * @param array{name:string,slug:string,level:int,description:string,permissions:array<int,int>} $payload
     */
    public function reservedSlugError(array $payload, ?Role $role = null): ?string
    {
        if (!$role instanceof Role || !(bool) $role->getAttribute('is_system')) {
            return null;
        }

        return in_array($payload['slug'], $this->roles->systemSlugs(), true)
            ? null
            : 'System roles must keep a reserved slug.';
    }
}
