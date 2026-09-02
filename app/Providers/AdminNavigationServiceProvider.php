<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Contracts\AdminActorInterface;
use App\Modules\Auth\Support\AuthManager;
use App\Support\AdminMenuRegistrar;
use League\Container\DefinitionContainerInterface;
use Marwa\Framework\Adapters\Event\AppBooted;
use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Config\BootstrapConfig;
use Marwa\Framework\Contracts\BootServiceProviderInterface;
use Marwa\Framework\Contracts\EventDispatcherInterface;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleRegistryInterface;

final class AdminNavigationServiceProvider extends ServiceProviderAdapter implements BootServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void {}

    public function boot(): void
    {
        $this->invalidateStaleRouteCache();

        $container = $this->getContainer();
        if (!$container->has(EventDispatcherInterface::class)) {
            return;
        }

        /** @var EventDispatcherInterface $events */
        $events = $container->get(EventDispatcherInterface::class);
        $events->listen(AppBooted::class, function (AppBooted $event) use ($container): void {
            $this->registerMenus($container);
        });
    }

    private function registerMenus(DefinitionContainerInterface $container): void
    {
        if (!$container->has(MenuRegistry::class)) {
            return;
        }

        $modules = [];

        if ($container->has(ModuleRegistryInterface::class)) {
            /** @var ModuleRegistryInterface $registry */
            $registry = $container->get(ModuleRegistryInterface::class);
            $modules = $registry->all();
        }

        $registrar = new AdminMenuRegistrar(
            $container->get(MenuRegistry::class),
            static function () use ($container): ?AdminActorInterface {
                if (!$container->has(AuthManager::class)) {
                    return null;
                }

                /** @var AuthManager $auth */
                $auth = $container->get(AuthManager::class);

                return $auth->user();
            },
        );
        $registrar->register($modules);
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
