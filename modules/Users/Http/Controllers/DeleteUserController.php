<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteUserController extends UsersController
{
    /**
     * @param array<string, mixed> $vars
     */
    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'You cannot delete the last admin user.');

            return $this->usersIndexRedirect();
        }

        if ($this->users->isActiveSessionUser($user, $this->auth)) {
            $this->flash('users.notice', 'You cannot delete the active session user.');

            return $this->usersIndexRedirect();
        }

        $this->activity->recordDeleted($user, $this->auth->user());
        $user->deleteOrFail();
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->usersIndexRedirect();
    }
}
