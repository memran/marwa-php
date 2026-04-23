<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BackgroundJobsManifestTest extends TestCase
{
    public function test_it_declares_a_sample_heartbeat_task(): void
    {
        $manifest = require dirname(__DIR__, 2) . '/modules/BackgroundJobs/manifest.php';

        self::assertIsArray($manifest);
        self::assertArrayHasKey('tasks', $manifest);
        self::assertArrayHasKey('heartbeat', $manifest['tasks']);
        self::assertSame('background-jobs:heartbeat', $manifest['tasks']['heartbeat']['command']);
        self::assertSame(['everyMinute' => true], $manifest['tasks']['heartbeat']['schedule']);
    }
}
