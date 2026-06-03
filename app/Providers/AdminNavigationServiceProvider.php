<?php

declare(strict_types=1);

namespace App\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Contracts\BootServiceProviderInterface;

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
