<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UpdateUserController extends UsersController
{
    /**
     * @param array<string, mixed> $vars
     */
    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        $validated = $this->validate($this->rules->update());

        $beforeState = $this->users->userSnapshot($user);
        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => $this->users->normalizeEmail((string) $validated['email']),
            'role' => trim((string) $validated['role']),
            'is_active' => array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 0,
        ];
        $password = array_key_exists('password', $validated) && $validated['password'] !== null
            ? (string) $validated['password']
            : null;
        $passwordChanged = $password !== null && $password !== '';

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'], (int) $user->getKey())) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role' => $afterState['role'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->userEditRedirect($user->getKey());
        }

        if ($this->users->isSelfProtectedAdmin($user, $afterState, $this->auth)) {
            $this->withErrors([
                'is_active' => ['The last admin user cannot disable themselves.'],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role' => $afterState['role'],
                'is_active' => false,
            ]);

            return $this->userEditRedirect($user->getKey());
        }

        if (!$passwordChanged && !$this->users->userStateHasChanges($beforeState, $afterState)) {
            $this->flash('users.notice', 'No changes detected.');

            return $this->userEditRedirect($user->getKey());
        }

        $this->users->updateUser($user, $afterState, $password);
        $this->flash('users.notice', 'User updated successfully.');

        return $this->usersIndexRedirect();
    }
}
