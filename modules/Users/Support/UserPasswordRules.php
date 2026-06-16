<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use Marwa\Framework\Validation\RequestValidator;
use Marwa\Support\Validation\ValidationException;

/**
 * Shared password validation for user forms and profile updates.
 *
 * The underlying validator requires an explicit confirmation field name
 * for the `confirmed` rule, so all rule sets declare it explicitly.
 */
final class UserPasswordRules
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>>
     */
    public function validateUserFormPassword(array $input, bool $isEdit): array
    {
        $rules = [
            'password' => $isEdit
                ? 'nullable|string|min:8|confirmed:password_confirmation'
                : 'required|string|min:8|confirmed:password_confirmation',
        ];

        return $this->run($input, $rules);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, array<int, string>>
     */
    public function validateProfilePassword(array $input): array
    {
        $rules = [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed:new_password_confirmation',
            'new_password_confirmation' => 'required|string',
        ];

        $messages = [
            'current_password.required' => 'Your current password is required.',
            'new_password.required' => 'The new password field is required.',
            'new_password.min' => 'The new password must be at least 8 characters.',
            'new_password.confirmed' => 'The new password confirmation does not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
        ];

        return $this->run($input, $rules, $messages);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @return array<string, array<int, string>>
     */
    private function run(array $input, array $rules, array $messages = []): array
    {
        try {
            (new RequestValidator())->validateInput($input, $rules, $messages);
        } catch (ValidationException $e) {
            return $e->errors()->all();
        }

        return [];
    }
}
