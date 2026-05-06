<?php

declare(strict_types=1);

namespace Tests\Feature;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Supports\Runtime;
use PHPUnit\Framework\TestCase;

final class SecurityRiskPruneScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        Runtime::setConsoleOverride(false);
        unset($GLOBALS['marwa_app']);
    }

    protected function tearDown(): void
    {
        Runtime::setConsoleOverride(null);
        unset($GLOBALS['marwa_app']);

        parent::tearDown();
    }

    public function test_security_risk_prune_task_is_registered(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->make(AppBootstrapper::class)->bootstrap();

        $taskNames = array_map(
            static fn ($task): string => $task->name(),
            $app->schedule()->tasks()
        );

        self::assertContains('security:risk-prune:scheduled', $taskNames);
    }
}
