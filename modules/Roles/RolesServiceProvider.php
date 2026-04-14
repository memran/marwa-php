<?php

declare(strict_types=1);

namespace App\Modules\Roles;

use League\Container\Container;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class RolesServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        //
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('roles', __DIR__ . '/resources/views');
    }
}