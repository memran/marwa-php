<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use Psr\Http\Message\ServerRequestInterface;

final class UserFormData
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

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
        $errors = [];

        if ($payload['name'] === '') {
            $errors['name'][] = 'The name field is required.';
        } elseif (mb_strlen($payload['name']) > 120) {
            $errors['name'][] = 'The name must not exceed 120 characters.';
        }

        if ($payload['email'] === '') {
            $errors['email'][] = 'The email field is required.';
        } elseif (filter_var($payload['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'The email must be a valid email address.';
        } else {
            $ignoreId = $user instanceof User ? (int) $user->getKey() : null;
            if ($this->users->isDuplicateEmail($payload['email'], $ignoreId)) {
                $errors['email'][] = 'The email has already been taken.';
            }
        }

        if ($payload['role_id'] <= 0) {
            $errors['role_id'][] = 'The role field is required.';
        }

        $isEdit = $user instanceof User;
        $password = $payload['password'];

        if (!$isEdit && $password === '') {
            $errors['password'][] = 'The password field is required.';
        } elseif ($password !== '' && mb_strlen($password) < 8) {
            $errors['password'][] = 'The password must be at least 8 characters.';
        } elseif ($password !== '' && $password !== $payload['password_confirmation']) {
            $errors['password_confirmation'][] = 'The password confirmation does not match.';
        }

        return $errors;
    }

    private function attr(?User $user, string $key, mixed $default): mixed
    {
        return $user instanceof User ? $user->getAttribute($key) : $default;
    }
}
