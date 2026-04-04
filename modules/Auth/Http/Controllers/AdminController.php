<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\AdminThemeManager;
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
        private AdminThemeManager $theme,
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
            'active_nav' => 'dashboard',
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
            'active_nav' => 'users',
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
            'active_nav' => 'roles',
            'roles' => Role::query()->orderBy('name')->get(),
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function toggleTheme(): ResponseInterface
    {
        $this->theme->toggle();

        return $this->redirect($this->safeAdminRedirect(), 303);
    }

    private function safeAdminRedirect(string $fallback = '/admin'): string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');

        if ($referer === '') {
            return $fallback;
        }

        $path = parse_url($referer, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || !str_starts_with($path, '/admin')) {
            return $fallback;
        }

        $query = parse_url($referer, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            return $path . '?' . $query;
        }

        return $path;
    }
}
