<?php

declare(strict_types=1);

namespace App\Modules\Users;

use App\Modules\Auth\Contracts\AdminUserProviderInterface;
use App\Modules\Users\Support\AdminUserProvider;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class UsersServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->container()->addShared(AdminUserProviderInterface::class, new AdminUserProvider(), true);
    }

    public function boot($app): void
    {
    }
}
