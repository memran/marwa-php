<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Support\WidgetRegistry;
use League\Container\Container;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DashboardServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(WidgetRegistry::class, function () {
            return new WidgetRegistry();
        });
    }

    public function boot($app): void
    {
        if (!$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('dashboard', __DIR__ . '/resources/views');
    }
}