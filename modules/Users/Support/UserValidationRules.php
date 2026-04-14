<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UserValidationRules
{
    /**
     * @return array<string, string>
     */
    public function store(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role_id' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function update(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role_id' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }
}
