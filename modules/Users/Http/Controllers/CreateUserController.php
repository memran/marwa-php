<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Psr\Http\Message\ResponseInterface;

final class CreateUserController extends UsersController
{
    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
        ]));
    }
}
