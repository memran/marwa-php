<?php

declare(strict_types=1);

namespace App\Modules\Users;

use Marwa\Framework\Supports\Runtime;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class UsersServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb()) {
            return;
        }

        $app->view()->addNamespace('users', __DIR__ . '/resources/views');
    }
}
