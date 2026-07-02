<?php

declare(strict_types=1);

namespace App\Modules\Security;

use App\Modules\Security\Support\SecurityRiskReport;
use League\Container\Container;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use Marwa\Module\Contracts\ModuleServiceProviderInterface;

final class SecurityServiceProvider implements ModuleServiceProviderInterface
{
    private Container $container;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function register($app): void
    {
        $this->container->addShared(SecurityRiskReport::class, function () use ($app): SecurityRiskReport {
            return new SecurityRiskReport($app->make(RiskAnalyzer::class));
        });
    }

    public function boot($app): void
    {
        if (!Runtime::isWeb() || !$app->has(View::class)) {
            return;
        }

        $app->view()->addNamespace('security', __DIR__ . '/resources/views');
    }
}
