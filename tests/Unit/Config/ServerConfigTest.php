<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class ServerConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        parent::tearDown();
    }

    public function testServerConfigProvidesSafeFallbacksWithoutSwoole(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/server.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('swoole', $config);
        self::assertSame('0.0.0.0', $config['swoole']['host']);
        self::assertIsInt($config['swoole']['options']['worker_num']);
        self::assertGreaterThanOrEqual(1, $config['swoole']['options']['worker_num']);
    }

    public function testServerAppDebugFollowsTheEnvironmentOnly(): void
    {
        $_ENV['APP_ENV'] = 'local';
        $_SERVER['APP_ENV'] = 'local';
        putenv('APP_ENV=local');

        $development = require dirname(__DIR__, 3) . '/config/server.php';

        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        putenv('APP_ENV=production');

        $production = require dirname(__DIR__, 3) . '/config/server.php';

        self::assertTrue($development['app']['debug']);
        self::assertFalse($production['app']['debug']);
    }
}
