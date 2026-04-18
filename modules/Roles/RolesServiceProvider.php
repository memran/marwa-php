<?php

declare(strict_types=1);

namespace App\Modules\Roles;

use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class RolesServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'roles',
                'label' => 'Roles',
                'url' => '/admin/roles',
                'parent' => 'admin.settings',
                'order' => 20,
                'icon' => 'shield',
            ]);
        }
    }

    public function boot($app): void
    {
    }
}
