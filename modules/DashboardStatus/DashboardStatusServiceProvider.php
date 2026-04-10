<?php

declare(strict_types=1);

namespace App\Modules\DashboardStatus;

use League\Container\Container;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DashboardStatusServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(DashboardStatusCards::class, new DashboardStatusCards());
    }

    public function boot($app): void
    {
    }
}
