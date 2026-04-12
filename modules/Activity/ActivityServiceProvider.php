<?php

declare(strict_types=1);

namespace App\Modules\Activity;

use Marwa\Framework\Supports\Runtime;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class ActivityServiceProvider implements ModuleServiceProviderInterface
{
    public function register($app): void
    {
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb()) {
            return;
        }

        $app->view()->addNamespace('activity', __DIR__ . '/resources/views');
    }
}
