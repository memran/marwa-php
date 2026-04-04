<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Modules\Auth\Policies\RolePolicy;
use App\Modules\Auth\Policies\UserPolicy;
use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Contracts\SessionInterface;
use Marwa\Framework\Supports\Config;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class AuthServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if (!method_exists($app, 'make') || !method_exists($app, 'add')) {
            return;
        }

        $config = $app->make(Config::class);
        $config->loadIfExists('auth.php');

        $session = $app->make(SessionInterface::class);

        $authManager = new AuthManager($config, $session);
        $userPolicy = new UserPolicy($authManager);
        $rolePolicy = new RolePolicy($authManager);

        $app->add(AuthManager::class, $authManager);
        $app->add(UserPolicy::class, $userPolicy);
        $app->add(RolePolicy::class, $rolePolicy);
        $app->add('auth.manager', $authManager);
    }

    public function boot($app): void
    {
    }
}
