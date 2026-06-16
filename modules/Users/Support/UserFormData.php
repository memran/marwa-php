<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Marwa\Framework\Validation\RequestValidator;
use Marwa\Support\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

final class UserFormData
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordRules $passwordRules,
    ) {}

    /**
     * @param array{mode:string,title:string,action:string,submit_label:string,user:?User} $extra
     * @param array<string, mixed> $old
     * @param array<string, list<string>> $errors
     * @return array<string, mixed>
     */
    public function formViewData(array $extra, array $old = [], array $errors = []): array
    {
        $user = $extra['user'] ?? null;

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
     * @return array{name:string,email:string,role_id:int,is_active:int,password:string,password_confirmation:string}
     */
    public function payload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        $input = is_array($body) ? $body : [];

        $password = trim((string) ($input['password'] ?? ''));
        $passwordConfirmation = trim((string) ($input['password_confirmation'] ?? ''));

        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'role_id' => (int) ($input['role_id'] ?? 0),
            'is_active' => (int) ($input['is_active'] ?? 1),
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ];
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int,password:string,password_confirmation:string} $payload
     * @return array<string, array<int, string>>
     */
    public function validate(array $payload, ?User $user = null): array
    {
        $isEdit = $user instanceof User;
        $errors = $this->runStandardValidation($payload);

        if (!isset($errors['email']) && $this->isDuplicateEmail($payload['email'], $user)) {
            $errors['email'][] = 'The email has already been taken.';
        }

        $passwordErrors = $this->passwordRules->validateUserFormPassword($payload, $isEdit);

        return array_merge($errors, $passwordErrors);
    }

    /**
     * @param array{name:string,email:string,role_id:int,is_active:int,password:string,password_confirmation:string} $payload
     * @return array<string, array<int, string>>
     */
    private function runStandardValidation(array $payload): array
    {
        $rules = [
            'name' => 'required|string|max:120',
            'email' => 'required|email',
            'role_id' => 'integer|min:1',
        ];

        $messages = [
            'name.required' => 'The name field is required.',
            'name.max' => 'The name must not exceed 120 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'role_id.min' => 'The role field is required.',
        ];

        try {
            (new RequestValidator())->validateInput($payload, $rules, $messages);
        } catch (ValidationException $e) {
            return $e->errors()->all();
        }

        return [];
    }

    private function isDuplicateEmail(string $email, ?User $user): bool
    {
        $ignoreId = $user instanceof User ? (int) $user->getKey() : null;

        return $this->users->isDuplicateEmail($email, $ignoreId);
    }

    private function attr(?User $user, string $key, mixed $default): mixed
    {
        return $user instanceof User ? $user->getAttribute($key) : $default;
    }
}
