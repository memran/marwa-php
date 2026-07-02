<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Modules\Settings\Support\SettingsApplier;
use App\Modules\Settings\Support\SettingsActivityLogger;
use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsLogoStorage;
use App\Modules\Settings\Support\SettingsMaintenance;
use App\Modules\Settings\Support\SettingsRepository;
use App\Modules\Settings\Support\SettingsStore;
use App\Support\ModuleDatabaseDependency;
use League\Container\Container;
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
        $this->container->addShared(SettingsLogoStorage::class, new SettingsLogoStorage());
        $this->container->addShared(SettingsActivityLogger::class, new SettingsActivityLogger());
        $this->container->addShared(SettingsMaintenance::class, new SettingsMaintenance(
            $app->make(\Marwa\Framework\Contracts\CacheInterface::class)
        ));
        $this->container->addShared(SettingsApplier::class, new SettingsApplier(
            $app->make(\Marwa\Framework\Supports\Config::class)
        ));
        $this->container->addShared(SettingsStore::class, new SettingsStore(
            $app->make(\Marwa\Framework\Contracts\CacheInterface::class),
            $this->container->get(SettingsCatalog::class),
            $this->container->get(SettingsRepository::class),
            $this->container->get(SettingsApplier::class)
        ));
    }

    public function boot($app): void
    {
        ModuleDatabaseDependency::boot(__DIR__, $app, function () use ($app): void {
            /** @var SettingsStore $store */
            $store = $app->make(SettingsStore::class);
            /** @var SettingsApplier $applier */
            $applier = $app->make(SettingsApplier::class);

            $applier->apply($store->all());
        });
    }
}
