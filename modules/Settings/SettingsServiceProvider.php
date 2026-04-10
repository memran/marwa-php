<?php

declare(strict_types=1);

namespace App\Modules\Settings;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class SettingsServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->set('module.settings.registered', true);
    }

    public function boot($app): void
    {
        $app->set('module.settings.booted', true);
    }
}
