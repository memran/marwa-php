<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Support\AdminPagination;
use App\Support\AdminSearch;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserValidationRules;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

abstract class UsersController extends Controller
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly UserFormData $forms,
        protected readonly UserValidationRules $rules,
        protected readonly AdminSearch $search,
        protected readonly AdminPagination $pagination,
        protected readonly AuthManager $auth
    ) {}

    protected function usersIndexRedirect(): ResponseInterface
    {
        return $this->redirect('/admin/users');
    }

    protected function userCreateRedirect(): ResponseInterface
    {
        return $this->redirect('/admin/users/create');
    }

    protected function userEditRedirect(int|string $id): ResponseInterface
    {
        return $this->redirect('/admin/users/' . $id . '/edit');
    }
}
