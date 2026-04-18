<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Authorization\Contracts\GateInterface;
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
        $adminTheme = trim((string) config('settings.lifecycle.theme.admin', config('view.adminTheme', 'admin'))) ?: 'admin';
        $databaseManagerEnabled = (bool) config(
            'settings.lifecycle.app.database_manager_enabled',
            !in_array((string) config('settings.lifecycle.app.env', config('app.env', 'production')), ['production', 'staging'], true)
        );

        $view->theme($adminTheme);
        $view->share('_current_path', $request->getUri()->getPath());

        $auth = app(AuthManager::class);
        $view->share('is_authenticated', $auth->check());

        $user = $auth->user();
        $gate = app(GateInterface::class);

        $isAdmin = false;
        $isSuperAdmin = false;
        $userRole = null;

        if ($user !== null) {
            $role = $user->role();
            $userRole = $role?->getAttribute('slug');
            $isAdmin = RolePolicy::isAdmin(is_string($userRole) ? $userRole : null);
            $isSuperAdmin = RolePolicy::isSuperAdmin(is_string($userRole) ? $userRole : null);
        }

        $view->share('user_role', $userRole);
        $view->share('is_admin_user', $isAdmin);
        $view->share('is_super_admin', $isSuperAdmin);
        $view->share('gate', $gate);
        $view->share('database_manager_enabled', $databaseManagerEnabled);
        $view->share('_system_date_format', (string) config('settings.lifecycle.system.date_format', 'Y-m-d'));
        $view->share('_system_time_format', (string) config('settings.lifecycle.system.time_format', 'H:i'));
        $view->share('_system_max_upload_size', (string) config('settings.lifecycle.system.max_upload_size', '10M'));
        $view->share('_security_password_policy', (string) config('settings.lifecycle.security.password_policy', ''));
        $view->share('_security_login_attempt_limit', (int) config('settings.lifecycle.security.login_attempt_limit', 5));
        $view->share('_security_two_factor_enabled', (bool) config('settings.lifecycle.security.two_factor_enabled', false));

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
