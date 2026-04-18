<?php

declare(strict_types=1);

namespace App\Modules\Activity;

use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class ActivityServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'activity',
                'label' => 'Activity',
                'url' => '/admin/activity',
                'parent' => 'admin.management',
                'order' => 40,
                'icon' => 'activity',
            ]);
        }
    }

    public function boot($app): void
    {
    }
}
