<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RestoreUserController extends UsersController
{
    /**
     * @param array<string, mixed> $vars
     */
    public function restore(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if (!empty($user->getAttribute('deleted_at'))) {
            $duplicate = $this->users->findDuplicateUserByEmail((string) $user->getAttribute('email'), (int) $user->getKey());

            if ($duplicate !== null) {
                $this->flash('users.notice', $this->users->duplicateUserMessage($duplicate));

                return $this->usersIndexRedirect();
            }
        }

        if ($this->users->restoreUser($user, $this->auth->user())) {
            $this->flash('users.notice', 'User restored successfully.');
        } else {
            $this->flash('users.notice', 'Unable to restore the selected user.');
        }

        return $this->usersIndexRedirect();
    }
}
