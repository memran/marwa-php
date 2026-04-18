<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\Gate;
use App\Modules\Auth\Support\RolePolicy;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminThemeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var View $view */
        $view = app(View::class);
        $previousTheme = $view->theme();
        $adminTheme = trim((string) config('view.adminTheme', 'admin')) ?: 'admin';

        $view->theme($adminTheme);
        $view->share('_current_path', $request->getUri()->getPath());

        $auth = app(AuthManager::class);
        $view->share('is_authenticated', $auth->check());

        $user = $auth->user();
        $gate = app(Gate::class);
        $gate->setUser($user);

        $isAdmin = false;
        $isSuperAdmin = false;
        $userRole = null;

        if ($user !== null) {
            $role = $gate->role();
            $userRole = $role?->getAttribute('slug');
            $isAdmin = RolePolicy::isAdmin(is_string($userRole) ? $userRole : null);
            $isSuperAdmin = RolePolicy::isSuperAdmin(is_string($userRole) ? $userRole : null);
        }

        $view->share('user_role', $userRole);
        $view->share('is_admin_user', $isAdmin);
        $view->share('is_super_admin', $isSuperAdmin);
        $view->share('gate', $gate);
        $view->share(
            'database_manager_enabled',
            (bool) env(
                'DATABASE_MANAGER_ENABLED',
                !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
            )
        );

        $view->share('can', function(string $permission) use ($gate): bool {
            return $gate->allows($permission);
        });

        $view->share('role_is', function(string $role) use ($userRole): bool {
            return RolePolicy::hasRole(is_string($userRole) ? $userRole : null, $role);
        });

        if (app()->has(MenuRegistry::class)) {
            $view->share('mainMenu', app(MenuRegistry::class)->tree());
        }

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }
}
