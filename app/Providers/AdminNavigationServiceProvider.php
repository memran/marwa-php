<?php

declare(strict_types=1);

namespace App\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Contracts\BootServiceProviderInterface;
use Marwa\Framework\Navigation\MenuRegistry;

final class AdminNavigationServiceProvider extends ServiceProviderAdapter implements BootServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        if (!$this->getContainer()->has(MenuRegistry::class)) {
            return;
        }

        /** @var MenuRegistry $menu */
        $menu = $this->getContainer()->get(MenuRegistry::class);

        $menu->addMany([
            [
                'name' => 'admin.user-space',
                'label' => 'User Space',
                'url' => '#',
                'order' => 10,
                'visible' => true,
            ],
            [
                'name' => 'admin.management',
                'label' => 'Management',
                'url' => '#',
                'order' => 20,
                'visible' => true,
            ],
            [
                'name' => 'admin.system',
                'label' => 'System',
                'url' => '#',
                'order' => 30,
                'visible' => true,
            ],
            [
                'name' => 'admin.settings',
                'label' => 'Settings',
                'url' => '#',
                'order' => 40,
                'visible' => true,
            ],
        ]);
    }
}
