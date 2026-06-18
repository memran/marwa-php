<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UserPasswordRules
{
    /**
     * @return array<string, string>
     */
    public function formRules(bool $isEdit): array
    {
        return [
            'password' => $isEdit
                ? 'nullable|string|min:8|confirmed:password_confirmation'
                : 'required|string|min:8|confirmed:password_confirmation',
            'password_confirmation' => $isEdit ? 'nullable|string' : 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function formMessages(): array
    {
        return [
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password_confirmation.required' => 'Please confirm your password.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function profileRules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed:new_password_confirmation',
            'new_password_confirmation' => 'required|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function profileMessages(): array
    {
        return [
            'current_password.required' => 'Your current password is required.',
            'new_password.required' => 'The new password field is required.',
            'new_password.min' => 'The new password must be at least 8 characters.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
        ];
    }
}
