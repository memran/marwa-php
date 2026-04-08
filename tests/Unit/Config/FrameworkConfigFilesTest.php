<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Marwa\Framework\Application;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FrameworkConfigFilesTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        parent::tearDown();
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function configFilesProvider(): array
    {
        return [
            'app' => [
                'config/app.php',
                ['name', 'title', 'base_path', 'debug', 'debugbar', 'key', 'defaultLocale', 'langPath', 'log', 'log_channel', 'providers', 'middlewares', 'maintenance', 'maintenance_time'],
            ],
            'bootstrap' => ['config/bootstrap.php', ['configCache', 'routeCache', 'moduleCache']],
            'cache' => ['config/cache.php', ['enabled', 'driver', 'sqlite', 'memory']],
            'console' => ['config/console.php', ['name', 'version', 'commands', 'discover', 'autoDiscover']],
            'database' => ['config/database.php', ['enabled', 'default', 'connections', 'migrationsPath']],
            'event' => ['config/event.php', ['listeners', 'subscribers']],
            'error' => ['config/error.php', ['enabled', 'appName', 'environment', 'renderer']],
            'http' => ['config/http.php', ['enabled', 'default', 'clients']],
            'logger' => ['config/logger.php', ['enable', 'filter', 'storage']],
            'mail' => ['config/mail.php', ['enabled', 'driver', 'from', 'smtp']],
            'module' => ['config/module.php', ['enabled', 'paths', 'cache', 'forceRefresh', 'commandPaths', 'commandConventions']],
            'notification' => ['config/notification.php', ['enabled', 'default', 'channels']],
            'queue' => ['config/queue.php', ['enabled', 'default', 'path', 'retryAfter']],
            'schedule' => ['config/schedule.php', ['enabled', 'driver', 'lockPath', 'file']],
            'security' => ['config/security.php', ['enabled', 'csrf', 'trustedHosts', 'risk']],
            'session' => ['config/session.php', ['enabled', 'autoStart', 'name', 'sameSite']],
            'storage' => ['config/storage.php', ['default', 'disks']],
            'view' => ['config/view.php', ['viewsPath', 'cachePath', 'debug', 'frontendTheme', 'adminTheme', 'defaultTheme']],
        ];
    }

    /**
     * @param list<string> $keys
     */
    #[DataProvider('configFilesProvider')]
    public function testFrameworkConfigFilesExposeBaseKeys(string $file, array $keys): void
    {
        $config = require dirname(__DIR__, 3) . '/' . $file;

        self::assertIsArray($config);

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $config);
        }
    }
}
