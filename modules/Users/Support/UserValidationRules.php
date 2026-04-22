<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UserValidationRules
{
    /**
     * @return array<string, string|callable>
     */
    public function store(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role_id' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'password' => ['required', 'string', 'min:8', $this->passwordConfirmationRule()],
        ];
    }

    /**
     * @return array<string, string|callable>
     */
    public function update(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'role_id' => 'required|integer',
            'is_active' => 'sometimes|boolean',
            'password' => ['nullable', 'string', 'min:8', $this->passwordConfirmationRule()],
        ];
    }

    /**
     * Treat an empty confirmation as "use the entered password".
     */
    private function passwordConfirmationRule(): callable
    {
        return static function (mixed $value, array $input, string $field = ''): true|string {
            if (!is_string($value) || $value === '') {
                return true;
            }

            $confirmation = $input['password_confirmation'] ?? null;

            if ($confirmation === null || $confirmation === '') {
                return true;
            }

            if (!is_string($confirmation)) {
                return 'The password confirmation does not match.';
            }

            return hash_equals($value, $confirmation)
                ? true
                : 'The password confirmation does not match.';
        };
    }
}
