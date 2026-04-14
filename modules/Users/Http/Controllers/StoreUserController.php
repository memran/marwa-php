<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;

final class StoreUserController extends UsersController
{
    public function store(): ResponseInterface
    {
        $validated = $this->validate($this->rules->store());

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $this->users->normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 1,
        ];

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'])) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->userCreateRedirect();
        }

        $this->users->createUser($afterState, (string) $validated['password'], $this->auth->user());
        $this->flash('users.notice', 'User created successfully.');

        return $this->usersIndexRedirect();
    }
}
