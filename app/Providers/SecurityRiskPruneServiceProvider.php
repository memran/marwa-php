<?php

declare(strict_types=1);

namespace App\Providers;

use Marwa\Framework\Adapters\ServiceProviderAdapter;
use Marwa\Framework\Contracts\BootServiceProviderInterface;

final class SecurityRiskPruneServiceProvider extends ServiceProviderAdapter implements BootServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return false;
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $app = $this->getContainer()->get(\Marwa\Framework\Application::class);

        $app->schedule()->command(
            'security:risk-prune',
            [],
            'security:risk-prune:scheduled'
        )
            ->description('Prunes security risk signals older than the configured retention window.')
            ->everyMinute()
            ->withoutOverlapping()
            ->when(static function (\Marwa\Framework\Application $app, \DateTimeImmutable $time): bool {
                return $time->format('H:i') === '00:00';
            });
    }
}
