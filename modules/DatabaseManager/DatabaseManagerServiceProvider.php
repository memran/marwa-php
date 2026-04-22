<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager;

use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DatabaseManagerServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'database-manager',
                'label' => 'Database',
                'url' => '/admin/database',
                'parent' => 'admin.system',
                'order' => 10,
                'icon' => 'database',
                'permission' => 'database.view',
                'visible' => static fn (): bool => (bool) env(
                    'DATABASE_MANAGER_ENABLED',
                    !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
                ),
            ]);
        }
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('database_manager', __DIR__ . '/resources/views');
    }
}
