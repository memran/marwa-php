<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Modules\Settings\Support\SettingsApplier;
use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsRepository;
use App\Modules\Settings\Support\SettingsStore;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class SettingsServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(SettingsCatalog::class, new SettingsCatalog());
        $this->container->addShared(SettingsRepository::class, new SettingsRepository());
        $this->container->addShared(SettingsApplier::class, new SettingsApplier(
            $app->make(\Marwa\Framework\Supports\Config::class)
        ));
        $this->container->addShared(SettingsStore::class, new SettingsStore(
            $app->make(\Marwa\Framework\Contracts\CacheInterface::class),
            $this->container->get(SettingsCatalog::class),
            $this->container->get(SettingsRepository::class),
            $this->container->get(SettingsApplier::class)
        ));

        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'settings',
                'label' => 'Settings',
                'url' => '/admin/settings',
                'parent' => 'admin.system',
                'order' => 10,
                'icon' => 'settings',
            ]);
        }
    }

    public function boot($app): void
    {
        /** @var SettingsStore $store */
        $store = $app->make(SettingsStore::class);
        /** @var SettingsApplier $applier */
        $applier = $app->make(SettingsApplier::class);

        $applier->apply($store->all());
    }
}
