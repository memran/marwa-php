<?php

declare(strict_types=1);

namespace Tests\Unit;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class QueueConfigTest extends TestCase
{
    public function test_it_exposes_database_driver_settings_by_default(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-queue-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);
        $GLOBALS['marwa_app'] = $app;

        try {
            $config = require __DIR__ . '/../../config/queue.php';

            self::assertIsArray($config);
            self::assertSame('database', $config['driver']);
            self::assertSame('default', $config['database']['connection']);
            self::assertSame('queue_jobs', $config['database']['table']);
            self::assertArrayHasKey('file', $config);
            self::assertArrayHasKey('path', $config['file']);
        } finally {
            unset($GLOBALS['marwa_app']);
            @rmdir($basePath);
        }
    }
}
