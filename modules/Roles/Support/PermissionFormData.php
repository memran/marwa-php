<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\PermissionRepository;

final class PermissionFormData
{
    public function __construct(
        private readonly PermissionRepository $permissions,
        private readonly Slugger $slugger,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,back_url?:string,permission:?Permission} $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra): array
    {
        $permission = $extra['permission'] ?? null;
        $groupOptions = $this->permissions->groupNames();
        $group = $permission instanceof Permission ? (string) $permission->getAttribute('group') : '';

        if ($group === '' && $groupOptions !== []) {
            $group = $groupOptions[0];
        }

        $defaults = [
            'name' => $permission instanceof Permission ? (string) $permission->getAttribute('name') : '',
            'slug' => $permission instanceof Permission ? (string) $permission->getAttribute('slug') : '',
            'group' => $group,
            'description' => $permission instanceof Permission ? (string) $permission->getAttribute('description') : '',
        ];

        $old = session('_old_input', []);
        if (is_array($old)) {
            foreach (['name', 'slug', 'group', 'description'] as $field) {
                if (array_key_exists($field, $old)) {
                    $defaults[$field] = (string) $old[$field];
                }
            }
        }

        return array_replace([
            'errors' => session('errors', []),
            'old' => $old,
            'form' => $defaults,
            'permission' => $permission,
            'groups' => $groupOptions,
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
            'group' => 'trim|required|string|max:120',
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
            'group.required' => 'The group field is required.',
            'group.max' => 'The group must not exceed 120 characters.',
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name:string,slug:string,group:string,description:string}
     */
    public function normalize(array $validated): array
    {
        $name = trim((string) ($validated['name'] ?? ''));
        $slug = trim((string) ($validated['slug'] ?? ''));

        if ($slug === '') {
            $slug = $this->slugger->slugify($name, 'custom-permission');
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'group' => trim((string) ($validated['group'] ?? '')),
            'description' => trim((string) ($validated['description'] ?? '')),
        ];
    }
}
