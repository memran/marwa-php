<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager;

use App\Modules\DatabaseManager\Support\RawSqlExecutor;
use App\Modules\DatabaseManager\Support\SqlQueryGuard;
use League\Container\Container;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DatabaseManagerServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(SqlQueryGuard::class, new SqlQueryGuard());
        $this->container->addShared(RawSqlExecutor::class, function () use ($app): RawSqlExecutor {
            return new RawSqlExecutor(
                $app->make(ConnectionManager::class),
                $this->container->get(SqlQueryGuard::class)
            );
        });
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('database_manager', __DIR__ . '/resources/views');
    }
}
