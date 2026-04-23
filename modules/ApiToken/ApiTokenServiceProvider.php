<?php

declare(strict_types=1);

namespace App\Modules\ApiToken;

use App\Modules\ApiToken\Support\ApiTokenRepository;
use App\Modules\ApiToken\Support\ApiTokenRepositoryInterface;
use League\Container\Container;
use Marwa\Framework\Navigation\MenuRegistry;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class ApiTokenServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $repository = new ApiTokenRepository();
        $this->container->addShared(ApiTokenRepository::class, $repository);
        $this->container->addShared(ApiTokenRepositoryInterface::class, $repository);
    }

    public function boot($app): void
    {
        if ($app->has(MenuRegistry::class)) {
            $app->make(MenuRegistry::class)->add([
                'name' => 'admin.api-tokens',
                'label' => 'API Tokens',
                'url' => '/admin/api-tokens',
                'parent' => 'admin.api',
                'order' => 10,
                'icon' => 'key',
                'permission' => 'api_token.view',
                'visible' => true,
            ]);
        }
    }
}
