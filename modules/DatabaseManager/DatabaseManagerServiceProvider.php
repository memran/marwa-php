<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager;

use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class DatabaseManagerServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('database_manager', __DIR__ . '/resources/views');
    }
}
