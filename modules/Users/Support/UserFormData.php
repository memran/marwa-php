<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Marwa\Framework\Validation\ValidationException;

final class UserFormData
{
    /**
     * @return array<string, string>
     */
    public function roles(): array
    {
        return [
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
            'viewer' => 'Viewer',
        ];
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra = []): array
    {
        $user = $extra['user'] ?? null;
        $defaults = [
            'name' => $user instanceof User ? (string) $user->getAttribute('name') : '',
            'email' => $user instanceof User ? (string) $user->getAttribute('email') : '',
            'role' => $user instanceof User ? (string) $user->getAttribute('role') : 'staff',
            'is_active' => $user instanceof User ? (bool) $user->getAttribute('is_active') : true,
        ];

        $old = session(ValidationException::OLD_INPUT_KEY, []);
        $errors = session(ValidationException::ERROR_BAG_KEY, []);

        if (is_array($old) && is_array($errors) && $errors !== []) {
            foreach (['name', 'email', 'role'] as $field) {
                if (array_key_exists($field, $old)) {
                    $defaults[$field] = (string) $old[$field];
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
