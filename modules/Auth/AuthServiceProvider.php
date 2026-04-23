<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Support\AdminUserResolver;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\PasswordResetMailer;
use App\Modules\Auth\Support\RolePolicy;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        $app->singleton(AdminUserResolver::class);
        $app->singleton(PasswordResetMailer::class);
        $app->singleton(AuthManager::class);
    }

    public function boot($app): void
    {
        RolePolicy::loadFromDatabase();
    }
}
