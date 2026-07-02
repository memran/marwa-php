<?php

declare(strict_types=1);

namespace App\Modules\Queue;

use App\Modules\Queue\Support\QueueRepository;
use League\Container\Container;
use Marwa\Framework\Queue\QueueManager;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class QueueServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(QueueRepository::class, function () use ($app) {
            return new QueueRepository($app->make(QueueManager::class));
        });
    }

    public function boot($app): void
    {
    }
}
