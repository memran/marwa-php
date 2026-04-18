<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UserFormData
{
    /**
     * @return array<int, string>
     */
    public function roles(): array
    {
        $roles = \App\Modules\Auth\Models\Role::newQuery()->getBaseBuilder()
            ->orderBy('level', 'desc')
            ->get();

        $result = [];
        foreach ($roles as $role) {
            $result[(int) $role['id']] = (string) $role['name'];
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra = []): array
    {
        $user = $extra['user'] ?? null;
        $role = $user instanceof User ? $user->role() : null;
        $defaults = [
            'name' => $user instanceof User ? (string) $user->getAttribute('name') : '',
            'email' => $user instanceof User ? (string) $user->getAttribute('email') : '',
            'role_id' => $role !== null ? (int) $role->getKey() : null,
            'is_active' => $user instanceof User ? (bool) $user->getAttribute('is_active') : true,
        ];

        $old = session('_old_input', []);
        $errors = session('errors', []);

        if (is_array($old) && is_array($errors) && $errors !== []) {
            foreach (['name', 'email', 'role_id'] as $field) {
                if (array_key_exists($field, $old)) {
                    if ($field === 'role_id') {
                        $defaults[$field] = (int) $old[$field];
                    } else {
                        $defaults[$field] = (string) $old[$field];
                    }
                }
            }

            if (array_key_exists('is_active', $old)) {
                $defaults['is_active'] = (bool) $old['is_active'];
            }
        }

        return array_replace([
            'errors' => $errors,
            'old' => $old,
            'form' => $defaults,
            'roles' => $this->roles(),
        ], $extra);
    }
}
