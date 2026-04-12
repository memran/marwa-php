<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowUserProfileController extends UsersController
{
    /**
     * @param array<string, mixed> $vars
     */
    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/profile', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }
}
