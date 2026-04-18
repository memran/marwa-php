<?php

declare(strict_types=1);

namespace App\Modules\Roles\Support;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Support\PermissionRepository;

final class PermissionFormData
{
    public function __construct(
        private readonly PermissionRepository $permissions,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,permission:?Permission} $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra): array
    {
        $permission = $extra['permission'] ?? null;
        $groupOptions = array_keys($this->permissions->grouped());
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
     * @return array{name:string,slug:string,group:string,description:string}
     */
    public function payload(): array
    {
        $name = trim((string) request('name', ''));
        $slug = trim((string) request('slug', ''));
        $group = trim((string) request('group', ''));
        $description = trim((string) request('description', ''));

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'group' => $group,
            'description' => $description,
        ];
    }

    /**
     * @param array{name:string,slug:string,group:string,description:string} $payload
     * @return array<string, array<int, string>>
     */
    public function validate(array $payload): array
    {
        $errors = [];

        if ($payload['name'] === '') {
            $errors['name'][] = 'The name field is required.';
        }

        if ($payload['slug'] === '') {
            $errors['slug'][] = 'The slug field is required.';
        }

        if ($payload['group'] === '') {
            $errors['group'][] = 'The group field is required.';
        }

        return $errors;
    }

    /**
     * @param array{name:string,slug:string,group:string,description:string} $payload
     * @return array<string, mixed>
     */
    public function oldInput(array $payload): array
    {
        return $payload;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? 'custom-permission' : $value;
    }
}
