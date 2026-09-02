<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use App\Support\AdminThemeResolver;
use App\Support\PermissionGate;
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
        $view->raw()->engine();
        $previousTheme = $view->theme();
        $adminTheme = app(AdminThemeResolver::class)->resolve(
            (string) config('settings.lifecycle.theme.admin', config('view.adminTheme', 'executive'))
        );

        $view->theme($adminTheme);
        $currentPath = $request->getUri()->getPath();
        $view->share('_current_path', $currentPath);
        $view->share('currentPath', $currentPath);

        $auth = app(AuthManager::class);
        $view->share('is_authenticated', $auth->check());

        $user = $auth->user();
        $gate = app(PermissionGate::class);

        $isAdmin = false;
        $isSuperAdmin = false;
        $userRole = null;
        $userName = null;
        $userEmail = null;

        if ($user instanceof AdminActorInterface) {
            $userName = trim((string) $user->getAttribute('name')) ?: null;
            $userEmail = trim((string) $user->getAttribute('email')) ?: null;
            $role = $user->role();
            $userRole = $role?->getAttribute('slug');
            $isAdmin = RolePolicy::isAdmin(is_string($userRole) ? $userRole : null);
            $isSuperAdmin = RolePolicy::isSuperAdmin(is_string($userRole) ? $userRole : null);
            $gate = $gate->withCurrentUserResolver(fn () => $user);
        }

        $view->share('user_role', $userRole);
        $view->share('user_name', $userName);
        $view->share('user_email', $userEmail);
        $view->share('is_admin_user', $isAdmin);
        $view->share('is_super_admin', $isSuperAdmin);
        $view->share('gate', $gate);

        /** @var MenuRegistry $menu */
        $menu = app(MenuRegistry::class);
        $view->share('mainMenu', $menu->tree());

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }
}
