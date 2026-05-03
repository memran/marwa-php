<?php

declare(strict_types=1);

namespace Tests\Unit;

use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Scheduling\Task;
use PHPUnit\Framework\TestCase;

final class BackgroundJobsSchedulerTest extends TestCase
{
    public function test_demo_scheduler_task_is_registered_and_records_success(): void
    {
        $app = new Application(dirname(__DIR__, 2));
        $app->make(AppBootstrapper::class)->bootstrap();

        $tasks = array_values(array_filter(
            $app->schedule()->tasks(),
            static fn (Task $task): bool => $task->name() === 'demo:scheduler-heartbeat'
        ));

        self::assertCount(1, $tasks);
        self::assertSame(
            'Demo scheduler heartbeat used to verify the framework scheduler is working.',
            $tasks[0]->description()
        );

        $summary = $app->schedule()->runDue(new \DateTimeImmutable('2026-05-03 12:00:00'));

        self::assertContains('demo:scheduler-heartbeat', $summary['ran']);

        $configuration = $app->schedule()->configuration();
        DB::table($configuration['database']['table'], $configuration['database']['connection'])
            ->where('name', '=', 'demo:scheduler-heartbeat')
            ->delete();
    }
}
