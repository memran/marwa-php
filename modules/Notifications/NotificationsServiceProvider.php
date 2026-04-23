<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Modules\Notifications\Support\NotificationRepository;
use App\Modules\Notifications\Support\NotificationService;
use League\Container\Container;
use Marwa\Framework\Adapters\ViewAdapter;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class NotificationsServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(NotificationRepository::class, new NotificationRepository());
        $this->container->addShared(NotificationService::class, new NotificationService(
            $this->container->get(NotificationRepository::class)
        ));

        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'notifications',
                'label' => 'Notifications',
                'url' => '/admin/notifications',
                'parent' => 'admin.user-space',
                'order' => 20,
                'icon' => 'bell',
                'permission' => 'notifications.view',
            ]);
        }
    }

    public function boot($app): void
    {
    }
}
