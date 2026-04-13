<?php

declare(strict_types=1);

namespace Tests\Unit;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class StarterConfigTest extends TestCase
{
    public function testAppConfigDefinesStarterErrorPagesAndBooleanDebugbar(): void
    {
        $config = require __DIR__ . '/../../config/app.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('debugbar', $config);
        self::assertIsBool($config['debugbar']);
        self::assertArrayNotHasKey('key', $config);
        self::assertSame('maintenance.twig', $config['maintenance']['template']);
        self::assertSame('errors/404.twig', $config['error404']['template']);
    }

    public function testDatabaseConfigUsesStarterDbEnvironmentVariablesAndFrameworkSqliteDefaults(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);

        foreach ([
            'APP_DEBUG',
            'DB_CONNECTION',
            'DB_NAME',
            'DB_DATABASE',
            'DB_HOST',
            'DB_PORT',
            'DB_USERNAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_CHARSET',
        ] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        putenv('APP_DEBUG=0');
        putenv('DB_CONNECTION=mysql');
        putenv('DB_HOST=mariadb');
        putenv('DB_PORT=3306');
        putenv('DB_NAME=marwa');
        putenv('DB_USER=marwa');
        putenv('DB_PASSWORD=secret');
        putenv('DB_CHARSET=utf8mb4');
        $_ENV['APP_DEBUG'] = '0';
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_HOST'] = 'mariadb';
        $_ENV['DB_PORT'] = '3306';
        $_ENV['DB_NAME'] = 'marwa';
        $_ENV['DB_USER'] = 'marwa';
        $_ENV['DB_PASSWORD'] = 'secret';
        $_ENV['DB_CHARSET'] = 'utf8mb4';
        $_SERVER['APP_DEBUG'] = '0';
        $_SERVER['DB_CONNECTION'] = 'mysql';
        $_SERVER['DB_HOST'] = 'mariadb';
        $_SERVER['DB_PORT'] = '3306';
        $_SERVER['DB_NAME'] = 'marwa';
        $_SERVER['DB_USER'] = 'marwa';
        $_SERVER['DB_PASSWORD'] = 'secret';
        $_SERVER['DB_CHARSET'] = 'utf8mb4';

        try {
            $config = require __DIR__ . '/../../config/database.php';

            self::assertSame('mysql', $config['default']);
            self::assertFalse($config['debug']);
            self::assertFalse($config['useDebugPanel']);
            self::assertSame(
                self::normalizePath($app->basePath('database/database.sqlite')),
                self::normalizePath($config['connections']['sqlite']['database'])
            );
            self::assertFalse($config['connections']['sqlite']['debug']);
            self::assertSame('mariadb', $config['connections']['mysql']['host']);
            self::assertSame(3306, $config['connections']['mysql']['port']);
            self::assertSame('marwa', $config['connections']['mysql']['database']);
            self::assertSame('marwa', $config['connections']['mysql']['username']);
            self::assertSame('secret', $config['connections']['mysql']['password']);
            self::assertSame('utf8mb4', $config['connections']['mysql']['charset']);
            self::assertFalse($config['connections']['mysql']['debug']);
        } finally {
            unset($GLOBALS['marwa_app']);
            foreach ([
                'APP_DEBUG',
                'DB_CONNECTION',
                'DB_HOST',
                'DB_PORT',
                'DB_NAME',
                'DB_USER',
                'DB_PASSWORD',
                'DB_CHARSET',
            ] as $key) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            }
            @rmdir($basePath);
        }
    }

    public function testViewConfigMatchesFrameworkDefaultsAndStarterAdminTheme(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);
        $GLOBALS['marwa_app'] = $app;

        try {
            $config = require __DIR__ . '/../../config/view.php';

            self::assertIsArray($config);
            self::assertSame(
                self::normalizePath($app->basePath('resources/views')),
                self::normalizePath($config['viewsPath'])
            );
            self::assertSame(
                self::normalizePath($app->basePath('storage/cache/views')),
                self::normalizePath($config['cachePath'])
            );
            self::assertIsBool($config['debug']);
            self::assertSame('.twig', $config['extension']);
            self::assertIsArray($config['cache']);
            self::assertArrayHasKey('enabled', $config['cache']);
            self::assertIsBool($config['cache']['enabled']);
            self::assertSame(
                self::normalizePath($app->basePath('resources/views/themes')),
                self::normalizePath($config['themePath'])
            );
            self::assertSame('default', $config['activeTheme']);
            self::assertSame('default', $config['fallbackTheme']);
            self::assertSame('admin', $config['adminTheme']);
            self::assertSame([
                \App\View\Extensions\SecurityViewExtension::class,
                \Marwa\View\Extension\AlpineExtension::class,
                \Marwa\View\Extension\DateExtension::class,
                \Marwa\View\Extension\HtmlExtension::class,
                \Marwa\View\Extension\ImageExtension::class,
                \Marwa\View\Extension\JsonExtension::class,
                \Marwa\View\Extension\ListExtension::class,
                \Marwa\View\Extension\MoneyExtension::class,
                \Marwa\View\Extension\NumberExtension::class,
                \Marwa\View\Extension\StatusExtension::class,
                \Marwa\View\Extension\StringPresentationExtension::class,
                \Marwa\View\Extension\TextExtension::class,
            ], $config['extensions']);
        } finally {
            unset($GLOBALS['marwa_app']);
            @rmdir($basePath);
        }
    }

    public function testSecurityConfigEnablesCsrfForUnsafeMethodsByDefault(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);
        $GLOBALS['marwa_app'] = $app;

        try {
            $config = require __DIR__ . '/../../config/security.php';

            self::assertIsArray($config);
            self::assertTrue($config['enabled']);
            self::assertTrue($config['csrf']['enabled']);
            self::assertSame('_token', $config['csrf']['field']);
            self::assertSame(['POST', 'PUT', 'PATCH', 'DELETE'], $config['csrf']['methods']);
        } finally {
            unset($GLOBALS['marwa_app']);
            @rmdir($basePath);
        }
    }

    public function testLoggerConfigUsesFrameworkShapeWithStarterDefaults(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);
        $GLOBALS['marwa_app'] = $app;

        foreach ([
            'APP_DEBUG',
            'LOG_CHANNEL',
            'LOG_LEVEL',
            'LOG_PREFIX',
        ] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        putenv('APP_DEBUG=1');
        putenv('LOG_CHANNEL=file');
        putenv('LOG_LEVEL=debug');
        $_ENV['APP_DEBUG'] = '1';
        $_ENV['LOG_CHANNEL'] = 'file';
        $_ENV['LOG_LEVEL'] = 'debug';
        $_SERVER['APP_DEBUG'] = '1';
        $_SERVER['LOG_CHANNEL'] = 'file';
        $_SERVER['LOG_LEVEL'] = 'debug';

        try {
            $config = require __DIR__ . '/../../config/logger.php';

            self::assertIsArray($config);
            self::assertTrue($config['enable']);
            self::assertSame([], $config['filter']);
            self::assertSame('file', $config['storage']['driver']);
            self::assertSame(
                self::normalizePath($app->basePath('storage/logs')),
                self::normalizePath($config['storage']['path'])
            );
            self::assertSame('marwa', $config['storage']['prefix']);
            self::assertSame('debug', $config['storage']['level']);
            self::assertArrayNotHasKey('max_bytes', $config['storage']);
        } finally {
            unset($GLOBALS['marwa_app']);
            foreach ([
                'APP_DEBUG',
                'LOG_CHANNEL',
                'LOG_LEVEL',
                'LOG_PREFIX',
            ] as $key) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            }
            @rmdir($basePath);
        }
    }

    public function testModuleConfigUsesStarterModuleCachePath(): void
    {
        $basePath = sys_get_temp_dir() . '/marwa-config-' . bin2hex(random_bytes(6));
        mkdir($basePath, 0777, true);
        $app = new Application($basePath);
        $GLOBALS['marwa_app'] = $app;

        foreach ([
            'MODULES_ENABLED',
            'APP_MODULE_CACHE',
        ] as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }

        try {
            $config = require __DIR__ . '/../../config/module.php';

            self::assertIsArray($config);
            self::assertTrue($config['enabled']);
            self::assertSame(
                self::normalizePath($app->basePath('storage/cache/modules.php')),
                self::normalizePath($config['cache'])
            );
        } finally {
            unset($GLOBALS['marwa_app']);
            foreach ([
                'MODULES_ENABLED',
                'APP_MODULE_CACHE',
            ] as $key) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            }
            @rmdir($basePath);
        }
    }

    private static function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
