<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use App\Support\PermissionGate;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Views\View;
use App\Modules\Users\Models\User;
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
        $gate = app(PermissionGate::class);

        $isAdmin = false;
        $isSuperAdmin = false;
        $userRole = null;
        $userName = null;

        if ($user instanceof User) {
            $userName = trim((string) $user->getAttribute('name')) ?: null;
            $role = $user->role();
            $userRole = $role?->getAttribute('slug');
            $isAdmin = RolePolicy::isAdmin(is_string($userRole) ? $userRole : null);
            $isSuperAdmin = RolePolicy::isSuperAdmin(is_string($userRole) ? $userRole : null);
        }

        $view->share('user_role', $userRole);
        $view->share('user_name', $userName);
        $view->share('is_admin_user', $isAdmin);
        $view->share('is_super_admin', $isSuperAdmin);
        $view->share('gate', $gate);

        $menuTree = [];
        try {
            $menuRegistry = app(MenuRegistry::class);
            if ($menuRegistry instanceof MenuRegistry) {
                $menuTree = $this->filteredMenu($menuRegistry->tree(), $gate, is_string($userRole) ? $userRole : null);
            }
        } catch (\Throwable) {
            $menuTree = [];
        }

        $view->share('mainMenu', $menuTree);

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function filteredMenu(array $items, PermissionGate $gate, ?string $userRole): array
    {
        $filtered = [];

        foreach ($items as $item) {
            $children = [];
            if (isset($item['children']) && is_array($item['children'])) {
                $children = $this->filteredMenu($item['children'], $gate, $userRole);
            }

            $visible = $this->menuItemVisible($item, $gate, $userRole);
            if (!$visible && $children === []) {
                continue;
            }

            $item['children'] = $children;
            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function menuItemVisible(array $item, PermissionGate $gate, ?string $userRole): bool
    {
        $permission = is_string($item['permission'] ?? null) ? trim((string) $item['permission']) : '';
        if ($permission !== '' && !$gate->allows($permission)) {
            return false;
        }

        $roles = $item['roles'] ?? null;
        if (is_array($roles) && $roles !== []) {
            if ($userRole === null || !in_array($userRole, array_map('strval', $roles), true)) {
                return false;
            }
        }

        $visible = $item['visible'] ?? true;
        if (is_bool($visible)) {
            return $visible;
        }

        if (is_callable($visible)) {
            try {
                return (bool) $visible($item);
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }
}
