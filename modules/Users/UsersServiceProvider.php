<?php

declare(strict_types=1);

namespace App\Modules\Users;

use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class UsersServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app->has(MenuRegistry::class)) {
            $menuRegistry = $app->make(MenuRegistry::class);

            $menuRegistry->add([
                'name' => 'users',
                'label' => 'Users',
                'url' => '/admin/users',
                'parent' => 'admin.management',
                'order' => 20,
                'icon' => 'users',
                'permission' => 'users.view',
            ]);
        }
    }

    public function boot($app): void
    {
    }
}
