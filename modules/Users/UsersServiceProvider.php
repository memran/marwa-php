<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Modules\Users\Models\User;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;
use App\Support\PermissionGate;


final class UsersServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if (!$app->has(MenuRegistry::class)) {
            return;
        }

        $app->make(MenuRegistry::class)->add([
            'name' => 'users',
            'label' => 'Users',
            'url' => '/admin/users',
            'parent' => 'admin.management',
            'order' => 10,
            'icon' => 'users',
            'permission' => 'users.view',
        ]);

        $gate = $app->make(PermissionGate::class);
        $gate->policy(User::class);
    }

    public function boot($app): void
    {
    }
}
