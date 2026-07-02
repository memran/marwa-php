<?php

declare(strict_types=1);

namespace App\Modules\Notifications;

use App\Modules\Notifications\Support\NotificationRepository;
use App\Modules\Notifications\Support\NotificationActivityLogger;
use App\Modules\Notifications\Support\NotificationService;
use League\Container\Container;
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
        $this->container->addShared(NotificationActivityLogger::class, new NotificationActivityLogger());
        $this->container->addShared(NotificationService::class, new NotificationService(
            $this->container->get(NotificationRepository::class),
            $this->container->get(NotificationActivityLogger::class)
        ));
    }

    public function boot($app): void {}
}
