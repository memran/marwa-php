<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Support\RolePolicy;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        RolePolicy::loadFromDatabase();
    }
}
