<?php

declare(strict_types=1);

namespace App\Modules\Security;

use League\Container\Container;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class SecurityServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('security', __DIR__ . '/resources/views');
    }
}
