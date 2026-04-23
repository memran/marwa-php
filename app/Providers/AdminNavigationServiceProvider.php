<?php

declare(strict_types=1);

namespace App\Providers;

use Marwa\Framework\Config\BootstrapConfig;
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
        $this->invalidateStaleRouteCache();
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
                'name' => 'admin.api',
                'label' => 'API',
                'url' => '#',
                'order' => 25,
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

    private function invalidateStaleRouteCache(): void
    {
        $app = app();
        if (!method_exists($app, 'basePath')) {
            return;
        }

        $routeCache = BootstrapConfig::defaults($app)['routeCache'];
        if (!is_file($routeCache)) {
            return;
        }

        $cacheMtime = filemtime($routeCache);
        if ($cacheMtime === false) {
            return;
        }

        $sourceMtime = $this->latestRouteSourceMtime($app->basePath());
        if ($sourceMtime <= $cacheMtime) {
            return;
        }

        @unlink($routeCache);
    }

    private function latestRouteSourceMtime(string $basePath): int
    {
        $latest = 0;
        $patterns = [
            $basePath . '/routes/*.php',
            $basePath . '/modules/*/routes/*.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                $mtime = filemtime($file);
                if ($mtime !== false && $mtime > $latest) {
                    $latest = $mtime;
                }
            }
        }

        return $latest;
    }
}
