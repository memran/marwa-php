<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserPolicy;
use Marwa\Framework\Authorization\Contracts\GateInterface;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;


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
        ]);

        // Bind the User policy to the Gate using the framework's resource method.
        $gate = $app->make(GateInterface::class);
        $gate->resource('users', User::class);
    }

    public function boot($app): void
    {
    }
}
