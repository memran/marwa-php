<?php

declare(strict_types=1);

namespace App\Support;

use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Support\RolePolicy;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Module;

final class AdminMenuRegistrar
{
    private const SECTIONS = [
        [
            'name' => 'admin.overview',
            'label' => 'Overview',
            'url' => '#',
            'order' => 10,
            'icon' => 'layout-dashboard',
        ],
        [
            'name' => 'admin.identity-access',
            'label' => 'Identity & Access',
            'url' => '#',
            'order' => 20,
            'icon' => 'users',
        ],
        [
            'name' => 'admin.administration',
            'label' => 'Administration',
            'url' => '#',
            'order' => 30,
            'icon' => 'server',
        ],
        [
            'name' => 'admin.system-logs',
            'label' => 'Notifications',
            'url' => '#',
            'order' => 40,
            'icon' => 'bell',
        ],
    ];

    /** @var \Closure(): (AdminActorInterface|null) */
    private \Closure $userResolver;

    /**
     * @param callable(): (AdminActorInterface|null) $userResolver
     */
    public function __construct(
        private readonly MenuRegistry $menu,
        callable $userResolver,
    ) {
        $this->userResolver = \Closure::fromCallable($userResolver);
    }

    /**
     * @param iterable<Module> $modules
     */
    public function register(iterable $modules): void
    {
        $this->menu->addMany(self::SECTIONS);
        $this->menu->add([
            'name' => 'admin.search',
            'label' => 'Search',
            'url' => '/admin/search',
            'parent' => 'admin.overview',
            'order' => 20,
            'icon' => 'search',
        ]);

        foreach ($modules as $module) {
            foreach ($this->moduleMenuEntries($module) as $entry) {
                $this->menu->add($this->withAccessVisibility($entry, $module->slug()));
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function moduleMenuEntries(Module $module): array
    {
        $menu = $module->get('menu');
        if ($menu === null) {
            return [];
        }

        if (!is_array($menu)) {
            throw new \UnexpectedValueException(sprintf(
                'Module [%s] menu declaration must be an array.',
                $module->slug(),
            ));
        }

        $entries = array_is_list($menu) ? $menu : [$menu];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                throw new \UnexpectedValueException(sprintf(
                    'Module [%s] menu entries must be arrays.',
                    $module->slug(),
                ));
            }
        }

        /** @var list<array<string, mixed>> $entries */
        return $entries;
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    private function withAccessVisibility(array $entry, string $moduleSlug): array
    {
        $this->validateParent($entry, $moduleSlug);
        $this->validateAccessMetadata($entry, $moduleSlug);

        $declaredVisibility = $entry['visible'] ?? true;
        if (!is_bool($declaredVisibility) && !is_callable($declaredVisibility)) {
            throw new \UnexpectedValueException(sprintf(
                'Module [%s] menu visibility must be a boolean or callable.',
                $moduleSlug,
            ));
        }

        $entry['visible'] = function (array $item) use ($declaredVisibility): bool {
            $isDeclaredVisible = is_bool($declaredVisibility)
                ? $declaredVisibility
                : (bool) $declaredVisibility($item);

            return $isDeclaredVisible && $this->currentUserCanSee($item);
        };

        return $entry;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function validateParent(array &$entry, string $moduleSlug): void
    {
        $parent = $entry['parent'] ?? null;
        $allowedParents = array_column(self::SECTIONS, 'name');

        if (!is_string($parent) || !in_array(trim($parent), $allowedParents, true)) {
            throw new \UnexpectedValueException(sprintf(
                'Module [%s] menu parent must be one of: %s.',
                $moduleSlug,
                implode(', ', $allowedParents),
            ));
        }

        $entry['parent'] = trim($parent);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function validateAccessMetadata(array &$entry, string $moduleSlug): void
    {
        $permission = $entry['permission'] ?? null;
        if ($permission !== null && (!is_string($permission) || trim($permission) === '')) {
            throw new \UnexpectedValueException(sprintf(
                'Module [%s] menu permission must be a non-empty string.',
                $moduleSlug,
            ));
        }

        if (is_string($permission)) {
            $entry['permission'] = trim($permission);
        }

        $roles = $entry['roles'] ?? null;
        if ($roles === null) {
            return;
        }

        if (!is_array($roles)) {
            throw new \UnexpectedValueException(sprintf(
                'Module [%s] menu roles must be an array of non-empty strings.',
                $moduleSlug,
            ));
        }

        $normalizedRoles = [];
        foreach ($roles as $role) {
            if (!is_string($role) || trim($role) === '') {
                throw new \UnexpectedValueException(sprintf(
                    'Module [%s] menu roles must be an array of non-empty strings.',
                    $moduleSlug,
                ));
            }

            $normalizedRoles[] = trim($role);
        }

        $entry['roles'] = $normalizedRoles;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function currentUserCanSee(array $item): bool
    {
        $permission = is_string($item['permission'] ?? null)
            ? trim($item['permission'])
            : '';
        $roles = $item['roles'] ?? null;

        if ($permission === '' && (!is_array($roles) || $roles === [])) {
            return true;
        }

        $user = ($this->userResolver)();
        if (!$user instanceof AdminActorInterface) {
            return false;
        }

        if ($permission !== '' && !$user->hasPermission($permission)) {
            return false;
        }

        if (!is_array($roles) || $roles === []) {
            return true;
        }

        $roleSlug = $user->role()?->getAttribute('slug');
        if (!is_string($roleSlug) || trim($roleSlug) === '') {
            return false;
        }

        foreach ($roles as $requiredRole) {
            if (is_string($requiredRole) && RolePolicy::hasRole($roleSlug, $requiredRole)) {
                return true;
            }
        }

        return false;
    }
}
