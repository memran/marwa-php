<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Marwa\Framework\Application;
use Marwa\Framework\Config\BootstrapConfig;
use PHPUnit\Framework\TestCase;

final class BootstrapConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        putenv('APP_CONFIG_CACHE');
        putenv('APP_ROUTE_CACHE');
        putenv('APP_MODULE_CACHE');
        unset($_ENV['APP_CONFIG_CACHE'], $_SERVER['APP_CONFIG_CACHE']);
        unset($_ENV['APP_ROUTE_CACHE'], $_SERVER['APP_ROUTE_CACHE']);
        unset($_ENV['APP_MODULE_CACHE'], $_SERVER['APP_MODULE_CACHE']);

        parent::tearDown();
    }

    public function testBootstrapCacheDefaultsUseTheStorageFolder(): void
    {
        $config = BootstrapConfig::defaults($GLOBALS['marwa_app']);

        self::assertSame('storage/cache/config.php', $config['configCache']);
        self::assertSame('storage/cache/routes.php', $config['routeCache']);
        self::assertSame('storage/cache/modules.php', $config['moduleCache']);
    }

    public function testBootstrapCachePathsCanBeOverriddenViaEnv(): void
    {
        $_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
        $_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';
        putenv('APP_CONFIG_CACHE=/tmp/config.php');

        $_ENV['APP_ROUTE_CACHE'] = '/tmp/routes.php';
        $_SERVER['APP_ROUTE_CACHE'] = '/tmp/routes.php';
        putenv('APP_ROUTE_CACHE=/tmp/routes.php');

        $_ENV['APP_MODULE_CACHE'] = '/tmp/modules.php';
        $_SERVER['APP_MODULE_CACHE'] = '/tmp/modules.php';
        putenv('APP_MODULE_CACHE=/tmp/modules.php');

        $config = BootstrapConfig::defaults($GLOBALS['marwa_app']);

        self::assertSame('/tmp/config.php', $config['configCache']);
        self::assertSame('/tmp/routes.php', $config['routeCache']);
        self::assertSame('/tmp/modules.php', $config['moduleCache']);
    }
}
