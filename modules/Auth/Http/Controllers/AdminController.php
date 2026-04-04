<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Policies\RolePolicy;
use App\Modules\Auth\Policies\UserPolicy;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Controllers\Controller;
use Marwa\Support\Security;
use Psr\Http\Message\ResponseInterface;

final class AdminController extends Controller
{
    public function __construct(
        private AuthManager $auth,
        private UserPolicy $userPolicy,
        private RolePolicy $rolePolicy
    ) {}

    public function dashboard(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof User) {
            return $this->redirect('/auth/login', 303);
        }

        return $this->view('@auth/admin/dashboard', [
            'title' => 'Admin dashboard',
            'user' => $user,
            'users_count' => User::query()->count(),
            'roles_count' => Role::query()->count(),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function users(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof User || !$this->userPolicy->viewAny($user)) {
            return $this->forbidden();
        }

        return $this->view('@auth/admin/users', [
            'title' => 'Users',
            'users' => User::query()->with('roles')->orderBy('name')->get(),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function roles(): ResponseInterface
    {
        $user = $this->auth->user();

        if (!$user instanceof User || !$this->rolePolicy->viewAny($user)) {
            return $this->forbidden();
        }

        return $this->view('@auth/admin/roles', [
            'title' => 'Roles',
            'roles' => Role::query()->orderBy('name')->get(),
            'csrf' => Security::csrfToken(),
        ]);
    }
}
