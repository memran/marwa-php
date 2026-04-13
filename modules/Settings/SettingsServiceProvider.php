<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use App\Modules\Settings\Support\SettingsApplier;
use App\Modules\Settings\Support\SettingsCatalog;
use App\Modules\Settings\Support\SettingsStore;
use League\Container\Container;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
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
    }

    public function boot($app): void
    {
        /** @var SettingsStore $store */
        $store = $app->make(SettingsStore::class);
        /** @var SettingsApplier $applier */
        $applier = $app->make(SettingsApplier::class);

        $applier->apply($store->all());

        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('settings', __DIR__ . '/resources/views');
    }
}
