<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditUserController extends UsersController
{
    /**
     * @param array<string, mixed> $vars
     */
    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }
}
