<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;

final class UserFormData
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordRules $passwordRules,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,user:?User} $extra
     * @return array<string, mixed>
     */
    public function formViewData(array $extra): array
    {
        $user = $extra['user'] ?? null;
        $old = session('_old_input', []);
        $errors = session('errors', []);

        $old = is_array($old) ? $old : [];
        $errors = is_array($errors) ? $errors : [];

        $defaults = [
            'name' => $this->attr($user, 'name', ''),
            'email' => $this->attr($user, 'email', ''),
            'role_id' => (int) $this->attr($user, 'role_id', 0),
            'is_active' => (int) $this->attr($user, 'is_active', 1),
        ];

        foreach (['name', 'email', 'role_id', 'is_active'] as $field) {
            if (!array_key_exists($field, $old)) {
                continue;
            }

            $defaults[$field] = match ($field) {
                'role_id', 'is_active' => (int) $old[$field],
                default => (string) $old[$field],
            };
        }

        $roleOptions = ['' => 'Select a role'];
        foreach ($this->users->roles() as $role) {
            $roleOptions[(int) $role->getKey()] = (string) $role->getAttribute('name');
        }

        return array_replace([
            'errors' => $errors,
            'old' => $old,
            'form' => $defaults,
            'user' => $user,
            'roles' => $roleOptions,
        ], $extra);
    }

    /**
     * @return array<string, string>
     */
    public function rules(bool $isEdit = false): array
    {
        return array_replace([
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role_id' => 'integer|min:1',
            'is_active' => 'integer|min:0',
        ], $this->passwordRules->formRules($isEdit));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_replace([
            'name.required' => 'The name field is required.',
            'name.max' => 'The name must not exceed 120 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'role_id.min' => 'The role field is required.',
            'is_active.min' => 'The status field must be valid.',
        ], $this->passwordRules->formMessages());
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name:string,email:string,role_id:int,is_active:int,password:string,password_confirmation:string}
     */
    public function normalize(array $validated): array
    {
        return [
            'name' => trim((string) ($validated['name'] ?? '')),
            'email' => User::normalizeEmail((string) ($validated['email'] ?? '')),
            'role_id' => (int) ($validated['role_id'] ?? 0),
            'is_active' => (int) ($validated['is_active'] ?? 1),
            'password' => trim((string) ($validated['password'] ?? '')),
            'password_confirmation' => trim((string) ($validated['password_confirmation'] ?? '')),
        ];
    }

    public function duplicateEmailError(string $email, ?User $user = null): ?string
    {
        $ignoreId = $user instanceof User ? (int) $user->getKey() : null;

        return $this->users->isDuplicateEmail($email, $ignoreId)
            ? 'The email has already been taken.'
            : null;
    }

    private function attr(?User $user, string $key, mixed $default): mixed
    {
        return $user instanceof User ? $user->getAttribute($key) : $default;
    }
}
