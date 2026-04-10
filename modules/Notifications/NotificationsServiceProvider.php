<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class NotificationsServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->set('module.notifications.registered', true);
    }

    public function boot($app): void
    {
        $app->set('module.notifications.booted', true);
    }
}
