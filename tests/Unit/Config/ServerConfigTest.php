<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class ServerConfigTest extends TestCase
{
    public function testServerConfigProvidesSafeFallbacksWithoutSwoole(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/server.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('swoole', $config);
        self::assertSame('0.0.0.0', $config['swoole']['host']);
        self::assertIsInt($config['swoole']['options']['worker_num']);
        self::assertGreaterThanOrEqual(1, $config['swoole']['options']['worker_num']);
    }
}
