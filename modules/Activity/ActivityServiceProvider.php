<?php

declare(strict_types=1);

namespace App\Modules\Activity;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class ActivityServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->set('module.activity.registered', true);
    }

    public function boot($app): void
    {
        $app->set('module.activity.booted', true);
    }
}
