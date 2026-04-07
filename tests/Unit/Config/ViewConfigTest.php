<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class ViewConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
        unset($_ENV['APP_DEBUG'], $_SERVER['APP_DEBUG']);

        parent::tearDown();
    }

    public function testViewCachePathIsDisabledInDevelopmentEnvironments(): void
    {
        foreach (['local', 'development'] as $environment) {
            $_ENV['APP_ENV'] = $environment;
            $_SERVER['APP_ENV'] = $environment;
            putenv('APP_ENV=' . $environment);

            $config = require dirname(__DIR__, 3) . '/config/view.php';

            self::assertNull($config['cachePath']);
            self::assertTrue($config['debug']);
        }
    }

    public function testViewCachePathRemainsEnabledInProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        putenv('APP_ENV=production');

        $config = require dirname(__DIR__, 3) . '/config/view.php';

        self::assertIsString($config['cachePath']);
        self::assertStringContainsString('/storage/cache/views', $config['cachePath']);
        self::assertFalse($config['debug']);
    }

    public function testAppDebugDoesNotControlTheViewLayer(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        putenv('APP_ENV=production');

        $_ENV['APP_DEBUG'] = '1';
        $_SERVER['APP_DEBUG'] = '1';
        putenv('APP_DEBUG=1');

        $config = require dirname(__DIR__, 3) . '/config/view.php';

        self::assertIsString($config['cachePath']);
        self::assertFalse($config['debug']);
    }
}
