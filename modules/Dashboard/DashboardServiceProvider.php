<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Support\WidgetRegistry;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Supports\Runtime;
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
        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'dashboard',
                'label' => 'Dashboard',
                'url' => '/admin/dashboard',
                'parent' => 'admin.user-space',
                'order' => 10,
                'icon' => 'layout-dashboard',
            ]);
        }

        $this->container->addShared(WidgetRegistry::class, function () {
            return new WidgetRegistry();
        });
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('dashboard', __DIR__ . '/resources/views');
    }
}
