<?php

declare(strict_types=1);

namespace App\Support;

final class AdminBreadcrumbs
{
    /**
     * @return list<array{label:string,url:?string,active:bool}>
     */
    public static function fromRequestPath(?string $path = null): array
    {
        $resolvedPath = $path ?? (string) request()->getUri()->getPath();
        $normalizedPath = trim($resolvedPath);

        if ($normalizedPath === '' || $normalizedPath === '/') {
            return [];
        }

        $segments = array_values(array_filter(explode('/', trim($normalizedPath, '/')), static fn (string $segment): bool => $segment !== ''));

        if ($segments === [] || strtolower($segments[0]) !== 'admin') {
            return [];
        }

        $adminSegments = array_slice($segments, 1);

        if ($adminSegments === []) {
            return [[
                'label' => 'Dashboard',
                'url' => '/admin',
                'active' => true,
            ]];
        }

        if (in_array($adminSegments[0], ['login', 'forgot-password', 'reset-password'], true)) {
            return [];
        }

        $module = self::moduleFor($adminSegments[0]);
        $moduleUrl = '/admin/' . $adminSegments[0];

        $trail = [[
            'label' => 'Dashboard',
            'url' => '/admin',
            'active' => false,
        ], [
            'label' => $module['module'],
            'url' => $moduleUrl,
            'active' => count($adminSegments) === 1,
        ]];

        $pageLabel = self::pageLabel($adminSegments, $module);

        if ($pageLabel === null || $pageLabel === $module['module']) {
            $trail[array_key_last($trail)]['active'] = true;

            return self::normalizeTrail($trail);
        }

        $trail[array_key_last($trail)]['active'] = false;
        $trail[] = [
            'label' => $pageLabel,
            'url' => null,
            'active' => true,
        ];

        return self::normalizeTrail($trail);
    }

    /**
     * @param list<array{label:string,url:?string,active:bool}> $trail
     * @return list<array{label:string,url:?string,active:bool}>
     */
    private static function normalizeTrail(array $trail): array
    {
        $normalized = [];

        foreach ($trail as $item) {
            $lastIndex = array_key_last($normalized);

            if (
                $lastIndex !== null
                && strcasecmp($normalized[$lastIndex]['label'], $item['label']) === 0
            ) {
                $normalized[$lastIndex] = [
                    'label' => $normalized[$lastIndex]['label'],
                    'url' => $item['url'] ?? $normalized[$lastIndex]['url'],
                    'active' => (bool) ($normalized[$lastIndex]['active'] || $item['active']),
                ];

                continue;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * @return array{module:string,page:string,noun:string}
     */
    private static function moduleFor(string $segment): array
    {
        return match ($segment) {
            'users', 'profile' => [
                'module' => 'Users',
                'page' => 'Users',
                'noun' => 'user',
            ],
            'roles', 'permissions' => [
                'module' => 'Roles & Permissions',
                'page' => $segment === 'roles' ? 'Roles' : 'Permissions',
                'noun' => $segment === 'roles' ? 'role' : 'permission',
            ],
            'settings' => [
                'module' => 'Settings',
                'page' => 'Settings',
                'noun' => 'settings',
            ],
            'notifications' => [
                'module' => 'Notifications',
                'page' => 'Notifications',
                'noun' => 'notification',
            ],
            'database-backups' => [
                'module' => 'Database Backups',
                'page' => 'Database Backups',
                'noun' => 'backup',
            ],
            'background-jobs' => [
                'module' => 'Background Jobs',
                'page' => 'Background Jobs',
                'noun' => 'job',
            ],
            'activity' => [
                'module' => 'Activity',
                'page' => 'Activity',
                'noun' => 'entry',
            ],
            'database' => [
                'module' => 'Database Manager',
                'page' => 'Database Manager',
                'noun' => 'query',
            ],
            'queue' => [
                'module' => 'Queue',
                'page' => 'Queue',
                'noun' => 'job',
            ],
            'security' => [
                'module' => 'Security',
                'page' => 'Security Risk',
                'noun' => 'report',
            ],
            default => [
                'module' => self::labelize($segment),
                'page' => self::labelize($segment),
                'noun' => self::labelize($segment, false),
            ],
        };
    }

    /**
     * @param list<string> $segments
     * @param array{module:string,page:string,noun:string} $module
     */
    private static function pageLabel(array $segments, array $module): ?string
    {
        $tail = $segments[array_key_last($segments)] ?? null;

        if ($tail === null) {
            return null;
        }

        if ($tail === $segments[0]) {
            return $module['page'];
        }

        if (in_array($tail, ['create', 'edit', 'show', 'view', 'restore', 'delete', 'profile'], true)) {
            return self::actionLabel($tail, $module);
        }

        if (is_numeric($tail)) {
            $previous = $segments[array_key_last($segments) - 1] ?? null;

            if (in_array($previous, ['create', 'edit', 'show', 'view', 'restore', 'delete'], true)) {
                return self::actionLabel($previous, $module);
            }

            return 'View ' . $module['noun'];
        }

        if ($tail === 'risk' && $segments[0] === 'security') {
            return 'Risk Report';
        }

        return self::labelize($tail);
    }

    private static function labelize(string $value, bool $capitalize = true): string
    {
        $label = str_replace(['-', '_'], ' ', trim($value));
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return $capitalize ? ucwords($label) : $label;
    }

    /**
     * @param array{module:string,page:string,noun:string} $module
     */
    private static function actionLabel(string $action, array $module): string
    {
        return match ($action) {
            'profile' => 'Profile',
            'view', 'show' => 'View ' . $module['noun'],
            'create' => 'Create ' . $module['noun'],
            'edit' => 'Edit ' . $module['noun'],
            'restore' => 'Restore ' . $module['noun'],
            'delete' => 'Delete ' . $module['noun'],
            default => self::labelize($action),
        };
    }
}
