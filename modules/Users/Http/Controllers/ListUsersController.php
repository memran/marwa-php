<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use Marwa\Framework\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;

final class ListUsersController extends UsersController
{
    public function index(): ResponseInterface
    {
        $search = $this->search->state();
        $users = $this->users->paginatedUsers($search['query'], $search['page']);
        $pagination = $this->pagination->viewData($users, '/admin/users', [
            'q' => $search['query'],
        ]);

        return $this->view('@users/index', [
            'users' => $users,
            'query' => $search['query'],
            'pagination' => $pagination,
            'errors' => session(ValidationException::ERROR_BAG_KEY, []),
            'notice' => session('users.notice'),
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }
}
