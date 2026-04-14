<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\Gate;
use App\Modules\Auth\Support\RolePolicy;
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
        
        $isAdmin = false;
        $isSuperAdmin = false;
        $userRole = null;
        
        if ($user !== null) {
            $userRole = $user->getAttribute('role');
            $isAdmin = RolePolicy::isAdmin($userRole);
            $isSuperAdmin = RolePolicy::isSuperAdmin($userRole);
        }
        
        $view->share('user_role', $userRole);
        $view->share('is_admin_user', $isAdmin);
        $view->share('is_super_admin', $isSuperAdmin);

        $gate = app(Gate::class);
        $gate->setUser($user);
        $view->share('gate', $gate);

        $view->share('can', function(string $permission) use ($gate): bool {
            return $gate->allows($permission);
        });
        
        $view->share('role_is', function(string $role) use ($userRole): bool {
            return RolePolicy::hasRole($userRole, $role);
        });

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }
}
