<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use App\Support\AdminThemeResolver;
use App\Support\PermissionGate;
use Marwa\Framework\Views\View;
use App\Modules\Users\Models\User;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminThemeMiddleware implements MiddlewareInterface
{
    private const SECTIONS = [
        ['name' => 'admin.overview', 'label' => 'Overview', 'order' => 10],
        ['name' => 'admin.identity-access', 'label' => 'Identity & Access', 'order' => 20],
        ['name' => 'admin.administration', 'label' => 'Administration', 'order' => 30],
        ['name' => 'admin.system-logs', 'label' => 'Systems Logs', 'order' => 40],
    ];

    private const SECTION_SLUG_MAP = [
        'Overview' => 'admin.overview',
        'Identity & Access' => 'admin.identity-access',
        'Administration' => 'admin.administration',
        'Systems Logs' => 'admin.system-logs',
    ];

    private const SECTION_ICONS = [
        'admin.overview' => 'layout-dashboard',
        'admin.identity-access' => 'users',
        'admin.administration' => 'server',
        'admin.system-logs' => 'bell',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var View $view */
        $view = app(View::class);
        $view->raw()->engine();
        $previousTheme = $view->theme();
        $adminTheme = app(AdminThemeResolver::class)->resolve(
            (string) config('settings.lifecycle.theme.admin', config('view.adminTheme', 'admin'))
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

        if ($user instanceof User) {
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

        $menuTree = $this->buildMenuTree($gate, $userRole);
        $view->share('mainMenu', $menuTree);

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMenuTree(PermissionGate $gate, ?string $userRole): array
    {
        $menuItems = $this->collectMenuItems();

        $grouped = [];
        foreach ($menuItems as $item) {
            $parent = $item['parent'];
            if (!isset($grouped[$parent])) {
                $grouped[$parent] = [];
            }
            $grouped[$parent][] = $item;
        }

        $tree = [];
        foreach (self::SECTIONS as $section) {
            $sectionName = $section['name'];
            $children = $grouped[$sectionName] ?? [];

            $filteredChildren = $this->filterItems($children, $gate, $userRole);
            if ($filteredChildren === []) {
                continue;
            }

            usort($filteredChildren, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

            $tree[] = [
                'name' => $sectionName,
                'label' => $section['label'],
                'url' => '#',
                'parent' => null,
                'order' => $section['order'],
                'icon' => self::SECTION_ICONS[$sectionName],
                'permission' => null,
                'roles' => null,
                'visible' => true,
                'children' => $filteredChildren,
            ];
        }

        return $tree;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectMenuItems(): array
    {
        $items = [];
        $order = 0;

        try {
            /** @var ModuleRegistryInterface $registry */
            $registry = app(ModuleRegistryInterface::class);

            foreach ($registry->all() as $module) {
                $manifestPath = $module->basePath() . DIRECTORY_SEPARATOR . 'manifest.php';
                if (!is_file($manifestPath)) {
                    continue;
                }

                try {
                    $fullManifest = require $manifestPath;
                } catch (\Throwable) {
                    continue;
                }

                if (!is_array($fullManifest)) {
                    continue;
                }

                $manifestMenu = $fullManifest['menu'] ?? null;
                if ($manifestMenu === null) {
                    continue;
                }

                $menuEntries = is_array($manifestMenu) && isset($manifestMenu[0])
                    ? $manifestMenu
                    : [$manifestMenu];

                foreach ($menuEntries as $entry) {
                    $item = $this->buildMenuItem($entry, $module->slug(), $order);
                    if ($item !== null) {
                        $items[] = $item;
                        $order++;
                    }
                }
            }
        } catch (\Throwable) {
        }

        $databaseManagerEnabled = (bool) config(
            'settings.lifecycle.app.database_manager_enabled',
            !in_array((string) config('settings.lifecycle.app.env', config('app.env', 'production')), ['production', 'staging'], true)
        );

        if ($databaseManagerEnabled) {
            $items[] = [
                'name' => 'admin.security-risk',
                'label' => 'Risk Report',
                'url' => '/admin/security/risk',
                'parent' => 'admin.system',
                'order' => 90,
                'icon' => 'shield-alert',
                'permission' => null,
                'roles' => null,
                'visible' => true,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>|null
     */
    private function buildMenuItem(array $item, string $moduleSlug, int $order): ?array
    {
        $section = is_string($item['section'] ?? null) ? trim((string) $item['section']) : '';
        $label = is_string($item['label'] ?? null) ? trim((string) $item['label']) : '';
        $route = is_string($item['route'] ?? null) ? trim((string) $item['route']) : '';
        $icon = is_string($item['icon'] ?? null) ? trim((string) $item['icon']) : null;
        $menuOrder = is_int($item['order'] ?? null) ? $item['order'] : $order;

        if ($section === '' || $label === '' || $route === '') {
            return null;
        }

        $parent = self::SECTION_SLUG_MAP[$section] ?? null;
        if ($parent === null) {
            return null;
        }

        $permissions = $item['permissions'] ?? null;
        $permission = is_array($permissions) && $permissions !== []
            ? (string) reset($permissions)
            : null;

        return [
            'name' => sprintf('admin.menu.%s.%s', $moduleSlug, $label),
            'label' => $label,
            'url' => $route,
            'parent' => $parent,
            'order' => $menuOrder,
            'icon' => $icon,
            'permission' => is_string($permission) && $permission !== '' ? $permission : null,
            'roles' => null,
            'visible' => true,
            'children' => [],
        ];
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function filterItems(array $items, PermissionGate $gate, ?string $userRole): array
    {
        $filtered = [];

        foreach ($items as $item) {
            $permission = is_string($item['permission'] ?? null) ? trim((string) $item['permission']) : '';
            if ($permission !== '' && !$gate->allows($permission)) {
                continue;
            }

            $roles = $item['roles'] ?? null;
            if (is_array($roles) && $roles !== []) {
                if ($userRole === null || !in_array($userRole, array_map('strval', $roles), true)) {
                    continue;
                }
            }

            $filtered[] = $item;
        }

        return $filtered;
    }
}
