<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class ModuleConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        putenv('APP_ENV');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        parent::tearDown();
    }

    public function testModuleCacheRefreshFollowsTheEnvironmentOnly(): void
    {
        foreach (['local', 'development'] as $environment) {
            $_ENV['APP_ENV'] = $environment;
            $_SERVER['APP_ENV'] = $environment;
            putenv('APP_ENV=' . $environment);

            $config = require dirname(__DIR__, 3) . '/config/module.php';

            self::assertTrue($config['forceRefresh']);
            self::assertSame('storage/cache/modules.php', $config['cache']);
        }

        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        putenv('APP_ENV=production');

        $config = require dirname(__DIR__, 3) . '/config/module.php';

        self::assertFalse($config['forceRefresh']);
        self::assertSame('storage/cache/modules.php', $config['cache']);
    }
}
